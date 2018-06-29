<?php

namespace solo\smarket\validate;

use pocketmine\item\Item as RealItem;
use solo\smarket\util\Util;

class ItemList{

	protected $items = [];

	public function add(Item $item){
		$this->items[Util::itemHash($item->getItem())] = $item;
	}

	public function contains(Item $item){
		return isset($this->items[Util::itemHash($item->getItem())]);
	}

	public function remove(Item $item){
		unset($this->items[Util::itemHash($item->getItem())]);
	}

	public function getByItem(RealItem $realItem){
		return $this->items[Util::itemHash($realItem)] ?? null;
	}

	public function getAll(){
		return $this->items;
	}
}
