<?php

namespace solo\smarket\command;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use solo\smarket\SMarket;

class BuyPriceCommand extends Command {

    private $owner;

    public function __construct(SMarket $owner) {
        parent::__construct("구매가", "아이템의 구매 가격을 설정합니다.", "/구매가 <가격>");
        $this->setPermission("smarket.command.buyprice");

        $this->owner = $owner;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(SMarket::$prefix . "인게임에서만 사용가능합니다.");
            return true;
        }
        if (!$sender->hasPermission($this->getPermission())) {
            $sender->sendMessage(SMarket::$prefix . "이 명령을 사용할 권한이 없습니다.");
            return true;
        }
        $market = $this->owner->getMarketManager()->getSelectedMarket($sender);
        if ($market === null) {
            $itemInHand = $sender->getInventory()->getItemInHand();
            if ($itemInHand->getId() !== Item::AIR) {
                $market = $this->owner->getMarketFactory()->getMarketByItem($itemInHand);
            }
        }
        if ($market === null) {
            $sender->sendMessage(SMarket::$prefix . "상점에서 아이템을 선택해주세요.");
            return true;
        }
        if (!isset($args [0]) || !is_numeric($args [0])) {
            $sender->sendMessage(SMarket::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
            return true;
        }
        $price = $args [0];
        $market->setBuyPrice($price);

        if ($price < 0) {
            $sender->sendMessage(SMarket::$prefix . $market->getItem()->getName() . " 아이템을 구매불가로 변경하였습니다.");
        } else {
            $sender->sendMessage(SMarket::$prefix . $market->getItem()->getName() . " 아이템의 구매가를 " . $this->owner->getEconomyAPI()->koreanWonFormat($price) . " 으로 설정하였습니다.");
        }
        return true;
    }
}
