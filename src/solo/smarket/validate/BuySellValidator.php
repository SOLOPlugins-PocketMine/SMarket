<?php

namespace solo\smarket\validate;

use solo\smarket\SMarket;

class BuySellValidator implements Validator{

	private $owner;

	public function __construct(SMarket $owner){
		$this->owner = $owner;
	}

	public function validate(){
		$invalidMarketInfoList = [];
		foreach($this->owner->getMarketFactory()->getAllMarket() as $market){
			if(
				$market->getBuyPrice() > 0
				&& $market->getSellPrice() > 0
				&& $market->getBuyPrice() < $market->getSellPrice()
		 ){
				$invalidMarketInfoList[] = new InvalidMarketInfo("상점 구매/판매", $market, $market->getBuyPrice(), [
					"대상 아이템 : " . $market->getItem()->getName(),
					"구매가 : " . $market->getBuyPrice(),
					"판매가 : " . $market->getSellPrice()
				]);
			}
		}
		return $invalidMarketInfoList;
	}
}
