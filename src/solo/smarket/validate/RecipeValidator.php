<?php

namespace solo\smarket\validate;

use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\inventory\FurnaceRecipe;

use solo\smarket\SMarket;
use solo\smarket\util\Util;

class RecipeValidator implements Validator {

    public function __construct(SMarket $owner) {
        $this->owner = $owner;
    }

    public function validate() {
        $itemList = new ItemList();

        //initial prices
        foreach ($this->owner->getMarketFactory()->getAllMarket() as $market) {
            if ($market->getBuyPrice() < 0) {
                continue;
            }
            $item = new Item($market->getItem(), $market->getBuyPrice());
            if (!$itemList->contains($item)) {
                $itemList->add($item);
            }
        }

        //initial recipes(get ingredients from recipe)
        foreach ($this->owner->getServer()->getCraftingManager()->getRecipes() as $recipe) {
            $ingredients = [];
            if ($recipe instanceof ShapedRecipe) {
                foreach ($recipe->getIngredientMap() as $row) {
                    foreach ($row as $realItem) {
                        $ingredients[] = $realItem;
                    }
                }
            } else if ($recipe instanceof ShapelessRecipe) {
                $ingredients = $recipe->getIngredientList();
            } else if ($recipe instanceof FurnaceRecipe) {
                $ingredients[] = $recipe->getInput();
            } else {
                continue;
            }

            // initial ingredientList(get items from ingredients)
            $ingredientList = new IngredientList();
            foreach ($ingredients as $realItem) {
                if ($realItem->getId() === 0) {// air
                    continue;
                }
                $item = new Item($realItem);
                if (!$itemList->contains($item)) {
                    $itemList->add($item);
                }
                $ingredientList->addIngredient($itemList->getByItem($item->getItem()));
            }

            $resultItem = $recipe->getResult();
            if ($resultItem->getCount() > 1) {
                $ingredientList->setDivider($resultItem->getCount());
            }
            $item = new Item($resultItem);
            if (!$itemList->contains($item)) {
                $itemList->add($item);
            }
            $itemList->getByItem($item->getItem())->addIngredientList($ingredientList);
        }

        $invalidMarketInfoList = [];
        foreach ($itemList->getAll() as $item) {
            $market = $this->owner->getMarketFactory()->getMarketByItem($item->getItem(), false);
            if ($market === null) {
                continue;
            }
            $sellPrice = $market->getSellPrice();

            foreach ($item->getIngredietLists() as $ingredientList) {
                $availableBuyPrice = $ingredientList->getPrice();
                if ($availableBuyPrice >= 0 && $sellPrice >= 0 && $availableBuyPrice < $sellPrice) {
                    $howTo = [
                            "조합재료 : " . $ingredientList->getName(),
                            "조합결과 : " . $item->getName(false),
                            "조합재료 구매가 : " . $availableBuyPrice,
                            "조합결과 판매가 : " . $sellPrice
                    ];
                    $invalidMarketInfoList[] = new InvalidMarketInfo("조합", $market, $availableBuyPrice, $howTo);
                }
            }
        }
        return $invalidMarketInfoList;
    }
}
