<?php

namespace solo\smarket\process;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\tile\Sign;
use pocketmine\tile\ItemFrame;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;

use solo\smarket\SMarket;
use solo\smarket\Process;
use solo\smarket\util\MarketAlreadyExistsException;

class MarketCreateProcess extends Process {

    public function __construct(Player $player) {
        parent::__construct($player);

        $this->player->sendMessage(SMarket::$prefix . "표지판이나 아이템 액자를 터치하면 상점이 생성됩니다.");
    }

    public function getName() {
        return "상점생성";
    }

    public function handlePlayerInteract(PlayerInteractEvent $event) {
        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $block = $event->getBlock();
            if (
                    $block->getId() == Block::WALL_SIGN
                    || $block->getId() == Block::SIGN_POST
                    || $block->getId() == Block::ITEM_FRAME_BLOCK
            ) {
                $tile = $block->getLevel()->getTile($block);
                if ($tile instanceof Sign || $tile instanceof ItemFrame) {
                    $item = $event->getPlayer()->getInventory()->getItemInHand();
                    if ($item->getId() === Item::AIR) {
                        return;
                    }
                    $event->setCancelled();
                    $market = SMarket::getInstance()->getMarketFactory()->getMarketByItem($item);

                    $marketManager = SMarket::getInstance()->getMarketManager();
                    try {
                        $marketManager->setMarket($block, $market);
                    } catch (MarketAlreadyExistsException $e) {
                        $this->player->sendMessage(SMarket::$prefix . $e->getMessage());
                        return;
                    }
                    $market->updateTile($tile);
                    $this->player->sendMessage(SMarket::$prefix . "상점을 생성하였습니다.");
                }
            }
        }
    }
}
