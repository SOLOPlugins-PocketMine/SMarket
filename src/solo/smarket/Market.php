<?php

namespace solo\smarket;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\tile\Tile;
use pocketmine\tile\Sign;
use pocketmine\tile\ItemFrame;

use solo\smarket\util\BuyException;
use solo\smarket\util\MarketDeserializeException;
use solo\smarket\util\SellException;
use solo\smarket\util\Util;

use onebone\economyapi\EconomyAPI;

class Market{

	private $id;

	private $item;

	private $buyPrice;
	private $sellPrice;

	public function __construct(int $id, Item $item, $buyPrice = -1, $sellPrice = -1){
		$this->id = $id;
		$this->item = Item::get($item->getId(), $item->getDamage(), 1, $item->getCompoundTag());

		$this->setBuyPrice($buyPrice);
		$this->setSellPrice($sellPrice);
	}

	public function getId(){
		return $this->id;
	}

	public function getItem(){
		return clone $this->item;
	}

	public function getName(){
		return Util::itemName($this->item);
	}

	public function getBuyPrice(){
		return $this->buyPrice;
	}

	public function setBuyPrice($price){
		$this->buyPrice =($price < 0) ? -1 : $price;
		return $this;
	}

	public function getSellPrice(){
		return $this->sellPrice;
	}

	public function setSellPrice($price){
		$this->sellPrice =($price < 0) ? -1 : $price;
		return $this;
	}

	public function buy(Player $player, int $count){
		if($count < 1){
			throw new BuyException("갯수는 양수로 입력해주세요.");
		}
		$price = $this->buyPrice * $count;
		if($price < 0){
			throw new BuyException("구매 불가능한 아이템입니다.");
		}
		$item = clone $this->item;
		$item->setCount($count);
		if(!$player->getInventory()->canAddItem($item)){
			throw new BuyException("인벤토리에 공간이 부족합니다.");
		}
		if(EconomyAPI::getInstance()->myMoney($player) < $price){
			throw new BuyException("돈이 부족합니다. 구매에 필요한 금액 : " . EconomyAPI::getInstance()->koreanWonFormat($price));
		}
		EconomyAPI::getInstance()->reduceMoney($player, $price);
		$player->getInventory()->addItem($item);
	}

	public function sell(Player $player, int $count){
		if($count < 1){
			throw new SellException("갯수는 양수로 입력해주세요.");
		}
		$price = $this->sellPrice * $count;
		if($price < 0){
			throw new SellException("판매 불가능한 아이템입니다.");
		}
		$item = clone $this->item;
		$item->setCount($count);
		if(Util::itemHollCount($player, $item) < $count){
			throw new SellException("판매할 아이템이 부족합니다.");
		}
		$player->getInventory()->removeItem($item);
		EconomyAPI::getInstance()->addMoney($player, $price);
	}

	public function updateTile(Tile $tile){
		if($tile instanceof Sign){
			$texts = [
				"§a[ 상점 ]",
				"§f" . $this->getName(),
				$this->buyPrice < 0 ? "§c구매 불가" : "§b구매 : " . EconomyAPI::getInstance()->koreanWonFormat($this->buyPrice),
				$this->sellPrice < 0 ? "§c판매 불가" : "§b판매 : " . EconomyAPI::getInstance()->koreanWonFormat($this->sellPrice)
			];
			$signText = $tile->getText();
			for($i = 0; $i < 4; $i++){
				if($signText[$i] !== $texts[$i]){
					$tile->setText(...$texts);
					Server::getInstance()->getLogger()->debug("[SMarket] old tile data was updated : " . implode(" ", $texts));
					return;
				}
			}
		}else if($tile instanceof ItemFrame){
			$display = $tile->getItem();
			$text =
				"§f" . $this->getName()
				. "\n" .($this->buyPrice < 0 ? "§c구매 불가" : "§b구매 : " . EconomyAPI::getInstance()->koreanWonFormat($this->buyPrice))
				. "\n" .($this->sellPrice < 0 ? "§c판매 불가" : "§b판매 : " . EconomyAPI::getInstance()->koreanWonFormat($this->sellPrice));
			if($display->getId() !== $this->item->getId() or $display->getDamage() !== $this->item->getDamage() or $display->getCustomName() !== $text){
				$display = Item::get($this->item->getId(), $this->item->getDamage(), 1, $this->item->getCompoundTag());
				$display->clearNamedTag();
				$display->setCustomName($text);
				$tile->setItem($display);
				Server::getInstance()->getLogger()->debug("[SMarket] old tile data was updated : " . str_replace("\n", " ", $text));
				return;
			}
		}
	}

	public function jsonSerialize(){
		return [
			"id" => $this->id,
			"item" => [
				"id" => $this->item->getId(),
				"damage" => $this->item->getDamage(),
				"nbt_b64" => base64_encode($this->item->getCompoundTag())
			],
			"buyPrice" => $this->buyPrice,
			"sellPrice" => $this->sellPrice
		];
	}

	public static function jsonDeserialize(array $data){
		$market =(new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
		if(!isset($data["id"]) or !isset($data["item"]["id"]) or !isset($data["buyPrice"]) or !isset($data["sellPrice"])){
			$values = [];
			foreach($data as $k => $v){
				$values[] = $k . ": " . $v;
			}
			throw new MarketDeserializeException("상점의 데이터가 일부 손실되었습니다.(" . implode(", ", $values) . ")");
		}
		$market->id = $data["id"];

		// Backwards compatibility
		if(isset($data["item"]["nbt"])){
			$nbt = $data["item"]["nbt"];
		}else if(isset($data["item"]["nbt_b64"])){
			$nbt = base64_decode($data["item"]["nbt_b64"], true);
		}else{
			$nbt = "";
		}

		$market->item = Item::get(
			(int) $data["item"]["id"],
			(int) $data["item"]["damage"] ?? 0,
			1,
			(string) $nbt
		);

		$market->buyPrice = $data["buyPrice"];
		$market->sellPrice = $data["sellPrice"];
		return $market;
	}
}
