<?php

namespace solo\smarket\command;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use solo\smarket\SMarket;
use solo\smarket\task\SellAllTask;
use solo\smarket\util\SellException;

class SellCommand extends Command{

	private $owner;

	public function __construct(SMarket $owner){
		parent::__construct("판매", "아이템을 판매합니다.", "/판매 <갯수/전체>");
		$this->setPermission("smarket.command.sell");

		$this->owner = $owner;
	}

	public function execute(CommandSender $sender, string $label, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage(SMarket::$prefix . "인게임에서만 사용할 수 있습니다.");
			return true;
		}
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(SMarket::$prefix . "이 명령을 실행할 권한이 없습니다.");
			return true;
		}
		$args [0] = $args [0] ?? "NaN";

		/*
		 * 모든 아이템을 판매(/판매 전체)
		 */
		if($args [0] == "전체"){
			try{
				(new SellAllTask($this->owner, $sender))->start();
			}catch(SellException $e){
				$sender->sendMessage(SMarket::$prefix . $e->getMessage());
			}
			return true;
		}

		/*
		 * 상점에서 선택한 아이템 또는 손에 들고있는 아이템을 판매(/판매 <수량>)
		 */
		$market = $this->owner->getMarketManager()->getSelectedMarket($sender);
		$count =(preg_match("/[0-9]+/", $args [0]) && intval($args [0]) > 0) ? intval($args [0]) : null;
		if($market === null){
			$itemInHand = $sender->getInventory()->getItemInHand();
			if($itemInHand->getId() !== Item::AIR){
				$market = $this->owner->getMarketFactory()->getMarketByItem($itemInHand);
				if($count === null){
					$count = $itemInHand->getCount();
				}
			}
		}
		if($market === null){
			$sender->sendMessage(SMarket::$prefix . "상점에서 아이템을 선택해주세요.");
			return true;
		}
		if($count === null){
			$sender->sendMessage(SMarket::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
			return true;
		}
		$money_before = $this->owner->getEconomyAPI()->myMoney($sender);
		try{
			$market->sell($sender, $count);
		}catch(SellException $e){
			$sender->sendMessage(SMarket::$prefix . $e->getMessage());
			return true;
		}
		$money_after = $this->owner->getEconomyAPI()->myMoney($sender);

		$sender->sendMessage(SMarket::$prefix . "§a" . $market->getItem()->getName() . " " . $count . "개§7를 판매하였습니다.");
		$sender->sendMessage(SMarket::$prefix . "판매 전 : " . $this->owner->getEconomyAPI()->koreanWonFormat($money_before) . "  /  판매 후 : §a" . $this->owner->getEconomyAPI()->koreanWonFormat($money_after) . "§7  /  얻은 금액 : §a" . $this->owner->getEconomyAPI()->koreanWonFormat($money_after - $money_before) . "§7");

		$this->owner->getMarketManager()->removeSelectedMarket($sender);
		return true;
	}
}
