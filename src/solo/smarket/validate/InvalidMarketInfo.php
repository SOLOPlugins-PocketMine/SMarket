<?php

namespace solo\smarket\validate;

use solo\smarket\Market;

class InvalidMarketInfo {

    private $type;
    private $market;
    private $availableBuyPrice;
    private $howTo;

    public function __construct(string $type, Market $market, $availableBuyPrice, array $howTo = []) {
        $this->type = $type;
        $this->market = $market;
        $this->availableBuyPrice = $availableBuyPrice;
        $this->howTo = $howTo;
    }

    public function getType() {
        return $this->type;
    }

    public function getMarket() {
        return $this->market;
    }

    public function getAvailableBuyPrice() {
        return $this->availableBuyPrice;
    }

    public function getSellPrice() {
        return $this->market->getSellPrice();
    }

    public function type() {
        return $this->type;
    }

    public function getHowTo() {
        return $this->howTo;
    }
}
