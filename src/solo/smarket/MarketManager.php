<?php

namespace solo\smarket;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\tile\Sign;
use pocketmine\tile\ItemFrame;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;

use solo\smarket\util\MarketAlreadyExistsException;
use solo\smarket\util\Util;

class MarketManager implements Listener {

    /** @var SMarket */
    public $owner;

    /** @var array */
    private $markets = [];

    /** @var int */
    private $itemFrameDropItemPacketId;

    public function __construct(SMarket $owner) {
        $this->owner = $owner;

        $this->load();

        $this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);

        $class_exists = function (string $class) {
            try {
                return interface_exists($class);
            } catch (\Throwable $e) {
                return false;
            }
        };
        // initial Packets
        if ($class_exists("\\pocketmine\\network\\mcpe\\protocol\\ProtocolInfo")) {
            $this->owner->getServer()->getLogger()->debug("[SMarket] \\pocketmine\\network\\mcpe\\protocol\\ProtocolInfo detected");
            $this->itemFrameDropItemPacketId = constant("\\pocketmine\\network\\mcpe\\protocol\\ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET");
        } else if ($class_exists("\\pocketmine\\network\\protocol\\ProtocolInfo")) {
            $this->owner->getServer()->getLogger()->debug("[SMarket] \\pocketmine\\network\\protocol\\ProtocolInfo detected");
            $this->itemFrameDropItemPacketId = constant("\\pocketmine\\network\\protocol\\ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET");
        } else {
            $this->owner->getServer()->getLogger()->debug("[SMarket] Unexpected protocol directory");
            $this->itemFrameDropItemPacketId = 0x47;
        }
    }

    public function getMarket(Position $pos) {
        return $this->markets[Util::positionHash($pos)] ?? null;
    }

    public function setMarket(Position $pos, Market $market, bool $override = false) {
        $hash = Util::positionHash($pos);
        if (isset($this->markets[$hash]) && !$override) {
            throw new MarketAlreadyExistsException("같은 좌표에 마켓이 겹칩니다 : x=" . $pos->getX() . ", y=" . $pos->getY() . ", z=" . $pos->getZ() . ", level=" . $pos->getLevel());
        }
        $this->markets[$hash] = $market;
    }

    public function removeMarket(Position $pos) {
        unset($this->markets[Util::positionHash($pos)]);
    }

    public function selectMarket(Player $player, Position $pos) {
        $this->selectedMarket[$player->getName()] = [
                "vector" => new Vector3($pos->x, $pos->y, $pos->z),
                "hash" => Util::positionHash($pos),
                "time" => time()
        ];
    }

    public function getSelectedMarket(Player $player) {
        if (isset($this->selectedMarket[$player->getName()])) {
            $data = $this->selectedMarket[$player->getName()];
            if (time() - $data["time"] < 30 && $data["vector"]->distance($player) < 10) {
                return $this->markets[$data["hash"]] ?? null;
            }
            $this->removeSelectedMarket($player);
        }
        return null;
    }

    public function removeSelectedMarket(Player $player) {
        unset($this->selectedMarket[$player->getName()]);
    }

    public function generateMarketForm(Market $market) {
        return [
                "§f" . $market->getItem()->getName(),
                $market->getBuyPrice() < 0 ? "§c구매 불가" : "§b구매 : " . $market->getBuyPrice(),
                $market->getSellPrice() < 0 ? "§c판매 불가" : "§b판매 : " . $market->getSellPrice()
        ];
    }

    public function generateMarketMessage(Player $player, Market $market) {
        $item = $market->getItem();

        $ret = [];
        if ($market->getBuyPrice() < 0 && $market->getSellPrice() < 0) {
            $ret = [
                    "이 아이템은 구매 / 판매가 불가능합니다."
            ];
        } else {
            $ret = [
                    "§b" . $market->getItem()->getName() . "§f 을(를) 구매 또는 판매하시겠습니까?§r§f",
                    "보유한 금액 : §l§b" . $this->owner->getEconomyAPI()->koreanWonFormat($this->owner->getEconomyAPI()->myMoney($player)) . "§r§f, 보유한 아이템 수 : §l§b" . Util::itemHollCount($player, $item) . "개§r§f", ($market->getBuyPrice() < 0 ? "구매 불가" : "구매가 : §l§b" . $this->owner->getEconomyAPI()->koreanWonFormat($market->getBuyPrice())) . "§r§f  /  " . ($market->getSellPrice() < 0 ? "판매 불가" : "판매가 : §l§b" . $this->owner->getEconomyAPI()->koreanWonFormat($market->getSellPrice())) . "§r§f",
                    "구매하려면 “§b/구매 <수량>§f”, 판매하려면 “§b/판매 <수량>§f”을 입력해주세요.§r§f"
            ];
        }
        if ($player->hasPermission("smarket.command.sellprice")) {
            $ret[] = "§o§d * /판매가 <가격> 명령어로 해당 아이템의 판매가를 변경할 수 있습니다.";
        }
        if ($player->hasPermission("smarket.command.buyprice")) {
            $ret[] = "§o§d * /구매가 <가격> 명령어로 해당 아이템의 구매가를 변경할 수 있습니다.";
        }
        return $ret;
    }

    public function handleInteract(PlayerInteractEvent $event) {
        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $block = $event->getBlock();
            if (
                    $block->getId() === Block::SIGN_POST
                    || $block->getId() === Block::WALL_SIGN
                    || $block->getId() === Block::ITEM_FRAME_BLOCK
            ) {
                if (($market = $this->getMarket($block)) === null) {
                    return;
                }
                $event->setCancelled();

                $tile = $block->getLevel()->getTile($block);
                $market->updateTile($tile);

                $event->getPlayer()->sendMessage("§r§a============================§r");
                foreach ($this->generateMarketMessage($event->getPlayer(), $market) as $message) {
                    $event->getPlayer()->sendMessage($message);
                }
                $event->getPlayer()->sendMessage("§r§a============================§r");

                $this->selectMarket($event->getPlayer(), $block);
            }
        }
    }

    /**
     * @priority HIGH
     *
     * @ignoreCancelled true
     */
    public function handleDataPacketReceive(DataPacketReceiveEvent $event) {
        if ($event->getPacket()->pid() === $this->itemFrameDropItemPacketId) {
            $player = $event->getPlayer();
            $tile = $player->getLevel()->getTile($pos = new Position($event->getPacket()->x, $event->getPacket()->y, $event->getPacket()->z, $player->getLevel()));
            if ($tile instanceof ItemFrame) {
                $market = $this->getMarket($pos);
                if ($market !== null) {
                    $event->setCancelled();
                    if ($player->hasPermission("smarket.manage.remove")) {
                        $this->removeMarket($pos);
                        $tile->setItem(null);
                        $player->sendMessage(SMarket::$prefix . "상점을 삭제하였습니다.");
                    } else {
                        $player->sendPopup("§c상점을 부술 수 없습니다");
                    }
                }
            }
        }
    }

    /**
     * @priority HIGH
     *
     * @ignoreCancelled true
     */
    public function handleBlockBreak(BlockBreakEvent $event) {
        if (
                $event->getBlock()->getId() == Block::SIGN_POST
                || $event->getBlock()->getId() == Block::WALL_SIGN
                || $event->getBlock()->getId() == Block::ITEM_FRAME_BLOCK
        ) {
            $market = $this->getMarket($event->getBlock());
            if ($market !== null) {
                if ($event->getPlayer()->hasPermission("smarket.manage.remove")) {
                    $this->removeMarket($event->getBlock());
                    $event->getPlayer()->sendMessage(SMarket::$prefix . "상점을 삭제하였습니다.");
                } else {
                    $event->setCancelled();
                    $evnet->getPlayer()->sendPopup("§c상점을 부술 수 없습니다");
                }
            }
        }
    }

    /**
     * @priority HIGH
     *
     * @ignoreCancelled true
     */
    //public function handleSignChange(SignChangeEvent $event){
    //  $lines = $event->getLines();
    //	if(array_shift($lines) == "상점생성"){
    //		$player = $event->getPlayer();
    //		if(!$player->hasPermission("smarket.manage.install")){
    //			return;
    //		}
    //    if(count($lines) == 0){
    //      return;
    //    }

    //    $item = Util::parseItem(array_shift($lines));
    //    if(!$item instanceof Item){
    //      return; // parse failed
    //    }

    //    $market = $this->owner->getMarketFactory()->getMarketByItem($item);

    //    $this->setMarket($event->getBlock(), $market, true); // Sign changes every word, so market should override prvious market
    //	}
    //}

    public function handlePlayerQuit(PlayerQuitEvent $event) {
        $this->removeSelectedMarket($event->getPlayer());
    }

    public function load() {
        $file = $this->owner->getDataFolder() . "installed_markets.json";
        if (file_exists($file)) {
            foreach (Util::jsonDecode(file_get_contents($file)) as $hash => $marketId) {
                $market = $this->owner->getMarketFactory()->getMarket($marketId);
                if ($market === null) {
                    $this->owner->getServer()->getLogger()->critical("[SMarket] Does MarketFactory data loss? Market Id : " . $marketId);
                    continue;
                }
                $this->markets[$hash] = $market;
            }
        }
    }

    public function save() {
        $data = [];
        foreach ($this->markets as $hash => $market) {
            $data[$hash] = $market->getId();
        }
        file_put_contents($this->owner->getDataFolder() . "installed_markets.json", json_encode($data));
    }
}
