<?php

namespace solo\smarket\command;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use solo\smarket\SMarket;
use solo\smarket\util\BuyException;

class BuyCommand extends Command{

	private $owner;

	public function __construct(SMarket $owner){
		parent::__construct("구매", "아이템을 판매합니다.", "/구매 <갯수>");
		$this->setPermission("smarket.command.buy");

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

		$market = $this->owner->getMarketManager()->getSelectedMarket($sender);
		if($market === null){
			$sender->sendMessage(SMarket::$prefix . "상점에서 아이템을 선택해주세요.");
			return true;
		}

		if(!isset($args [0]) || !is_numeric($args [0]) ||($count = intval($args [0])) <= 0){
			$sender->sendMessage(SMarket::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
			return true;
		}

		$money_before = $this->owner->getEconomyAPI()->myMoney($sender);
		try{
			$market->buy($sender, $count);
		}catch(BuyException $e){
			$sender->sendMessage(SMarket::$prefix . $e->getMessage());
			return true;
		}
		$money_after = $this->owner->getEconomyAPI()->myMoney($sender);

		$sender->sendMessage(SMarket::$prefix . "§a" . $market->getItem()->getName() . " " . $count . "개§7를 구매하였습니다.");
		$sender->sendMessage(SMarket::$prefix . "구매 전 : " . $this->owner->getEconomyAPI()->koreanWonFormat($money_before) . "  /  구매 후 : §a" . $this->owner->getEconomyAPI()->koreanWonFormat($money_after) . "§7  /  소비한 금액 : §a" . $this->owner->getEconomyAPI()->koreanWonFormat($money_before - $money_after) . "§7");

		$this->owner->getMarketManager()->removeSelectedMarket($sender);
		return true;
	}
}
