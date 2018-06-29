<?php

namespace solo\smarket\task;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;

use solo\smarket\SMarket;
use solo\smarket\util\SellException;
use solo\smarket\util\Util;

class SellAllTask extends Task{

	private static $tasks = [];


	private $owner;
	/** @var Player */
	private $player;

	private $inventoryIndex = 0;

	private $totalEarned = 0;

	private $totalSelled = [];

	public function __construct(SMarket $owner, Player $player){
	$this->owner = $owner;
		$this->player = $player;
	}

	public function start(){
		$name = $this->player->getName();
		if(isset(self::$tasks[$name])){
			throw new SellException("이미 아이템을 판매중입니다.");
		}
		self::$tasks[$name] = $this;
		$this->owner->getScheduler()->scheduleRepeatingTask($this, 4);
	}

	public function onRun(int $currentTick){
		if(!$this->player->isOnline()){
			$this->close();
			return;
		}

		while($this->inventoryIndex < $this->player->getInventory()->getSize()){
			$content = $this->player->getInventory()->getItem($this->inventoryIndex++);
			if(!$content instanceof Item || $content->getId() == Item::AIR){
				continue;
			}
			$market = $this->owner->getMarketFactory()->getMarketByItem($content, false);
			if($market === null || $market->getSellPrice() <= 0){
				continue;
			}

			$money_before = $this->owner->getEconomyAPI()->myMoney($this->player);
			try{
				$market->sell($this->player, $content->getCount());
			}catch(SellException $e){
				$this->owner->getServer()->getLogger()->debug("[SMarket] " . $this->player->getName() . " tried to sell " . $content->getName() . ", but exception throwed : " . $e->getMessage());
				continue;
			}
			$money_after = $this->owner->getEconomyAPI()->myMoney($this->player);

			$earned = $money_after - $money_before;
			$this->totalEarned += $earned;

			$itemname = Util::itemName($content);
			$this->totalSelled[$itemname] =($this->totalSelled[$itemname] ?? 0) + $content->getCount();

			if($this->owner->getSetting()->get("sell-all-at-once")){
				continue;
			}else{
				$this->player->sendPopup("§b" . $itemname . " " . $content->getCount() . "개§7를 " . $this->owner->getEconomyAPI()->koreanWonFormat($earned) . "에 판매하였습니다.(합: " . $this->owner->getEconomyAPI()->koreanWonFormat($this->totalEarned) . ")");
				return; // continue sell(Delayed)
			}
		}
		// end
		$this->player->sendMessage(SMarket::$prefix . "판매된 아이템 : " . implode(", ", array_map(function($key, $value){return $key . " " . $value . "개"; }, array_keys($this->totalSelled), $this->totalSelled)));
		$this->player->sendMessage(SMarket::$prefix . "판매 합계 : " . $this->owner->getEconomyAPI()->koreanWonFormat($this->totalEarned));
		$this->close();
	}

	public function close(){
		unset(self::$tasks[$this->player->getName()]);
		unset($this->player);
		$this->owner->getScheduler()->cancelTask($this->getTaskId());
	}
}
