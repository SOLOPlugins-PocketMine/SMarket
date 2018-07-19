<?php

namespace solo\smarket\validate;

use pocketmine\item\Item as RealItem;

/**
 * 조합 Validation을 위한 클래스입니다.
 */
class Ingredient{

	protected $item;
	protected $price;
	protected $checkMeta = true;

	public function __construct(RealItem $item, $price = -1){
		if($item->getDamage() == -1){
			$this->checkMeta = false;
			$this->item = RealItem::get($item->getId(), 0, 1, $item->getCompoundTag());
		}else{
			$this->item = RealItem::get($item->getId(), $item->getDamage(), 1, $item->getCompoundTag());
		}
		$this->price = $price;
	}

	public function getName(){
		return $this->item->getName();
	}

	public function getItem(){
		return $this->item;
	}

	// if damage is -1, do not check meta
	public function equals(Ingredient $ingredient){
		return $ingredient->item->equals($this->item, $this->checkMeta, $this->checkMeta);
	}

	public function setPrice($price){
		$this->price =($price < 0) ? -1 : $price;
	}

	public function getPrice(){
		return $this->price;
	}
}
