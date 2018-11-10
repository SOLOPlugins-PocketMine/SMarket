<?php

namespace solo\smarket\validate;

use solo\smarket\util\Util;

class Item extends Ingredient {

    private $ingredientLists = [];

    private $lowestPrice = null;

    public function getName(bool $price = true) {
        return parent::getName() . ($price ? "(" . $this->getLowestPrice() . ")" : "");
    }

    public function getIngredietLists() {
        return $this->ingredientLists;
    }

    public function getLowestPrice(ItemList $checked = null) {
        if ($checked !== null) {
            if ($checked->contains($this)) {
                return $this->lowestPrice ?? $this->price;
            } else {
                $checked->add($this);
            }
        } else {
            $checked = new ItemList();
            $checked->add($this);
        }
        $this->lowestPrice = $this->price;
        foreach ($this->ingredientLists as $ingredientList) {
            $price = $ingredientList->getPrice($checked);
            if ($price < 0) {
                continue;
            }
            $this->lowestPrice = min($price, $this->lowestPrice);
        }
        return $this->lowestPrice;
    }

    public function hasIngredientList(IngredientList $list) {
        foreach ($this->ingredientLists as $checkList) {
            if ($checkList->equals($list)) {
                return false;
            }
        }
        return true;
    }

    public function addIngredientList(IngredientList $list) {
        $this->ingredientLists[] = $list;
    }
}
