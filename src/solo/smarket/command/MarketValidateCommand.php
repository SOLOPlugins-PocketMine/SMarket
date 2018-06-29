<?php

namespace solo\smarket\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\inventory\FurnaceRecipe;
use solo\smarket\SMarket;
use solo\smarket\util\Util;

class MarketValidateCommand extends Command{

	private $owner;

	public function __construct(SMarket $owner){
		parent::__construct("상점가격점검", "상점의 가격을 점검합니다.", "/상점가격점검");
		$this->setPermission("smarket.command.validate");

		$this->owner = $owner;
	}

	public function execute(CommandSender $sender, string $label, array $args) : bool{
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(SMarket::$prefix . "이 명령을 사용할 권한이 없습니다.");
			return true;
		}

		$results = [ ];
		foreach($this->owner->getAllValidator() as $validator){
			foreach($validator->validate() as $result){
				$results [] = $result;
			}
		}
		foreach($results as $result){
			$sender->sendMessage(SMarket::$prefix . $result->getType() . " 을(를) 통해 차익이 발생합니다 : ");
			foreach($result->getHowTo() as $text){
				$sender->sendMessage("§7     " . $text);
			}
		}
		$invalid = count($results);
		$sender->sendMessage(SMarket::$prefix . "상점 가격표를 모두 점검하였습니다.(" .($invalid == 0 ? "이상 없음" : $invalid . "개 발견됨") . ")");
		return true;

		// $invalid = 0;

		// $marketFactory = $this->owner->getMarketFactory();

		/*
		 * Check Market
		 */
		// foreach($marketFactory->getAllMarket() as $market){
		// if($market->getSellPrice() > $market->getBuyPrice()){
		// $sender->sendMessage(
		// SMarket::$prefix . "상점 구매/판매 을(를) 통해 차익이 발생합니다 : "
		// . Util::itemName($market->getItem()) . "(구매가 " . $market->getBuyPrice() . ", 판매가 " . $market->getSellPrice() . ")"
		//);
		// $invalid++;
		// }
		// }

		// TODO: check Drops

		/*
		 * Check Craft
		 */
		// foreach($this->owner->getServer()->getCraftingManager()->getRecipes() as $recipe){
		// $ingredients = [];
		// $result = $recipe->getResult();
		// $market = $marketFactory->getMarketByItem($result, false);
		// if($market === null || $market->getSellPrice() < 0){// if market not exists, or cannot sell
		// continue;
		// }
		// $resultSellPrice = $market->getSellPrice();

		// $recipeType = "";
		// if($recipe instanceof ShapedRecipe){
		// $recipeType = "조합";
		// foreach($recipe->getIngredientMap() as $row){
		// foreach($row as $ingredient){
		// $ingredients[] = $ingredient;
		// }
		// }
		// }else if($recipe instanceof ShapelessRecipe){
		// $recipeType = "조합";
		// $ingredients = $recipe->getIngredientList();
		// }else if($recipe instanceof FurnaceRecipe){
		// $recipeType = "화로";
		// $ingredients[] = $recipt->getInput();
		// }else{
		// continue;
		// }

		// $ingredientsBuyPrice = 0;
		// foreach($ingredients as $key => $ingredient){
		// if($ingredient->getId() === Item::AIR){
		// unset($ingredients[$key]);
		// continue;
		// }
		// $market = $marketFactory->getMarketByItem($ingredient, false);
		// if($market === null || $market->getBuyPrice() < 0){
		// continue 2;
		// }
		// $ingredientsBuyPrice += $market->getBuyPrice();
		// }

		// if($resultSellPrice > $ingredientsBuyPrice){
		// $ingStrings = [];
		// foreach($ingredients as $ingredient){
		// $itemname = Util::itemName($ingredient);
		// $ingStrings[$itemname] =($ingStrings[$itemname] ?? 0) + $ingredient->getCount();
		// }
		// $arr = [];
		// foreach($ingStrings as $itemname => $count){
		// $arr[] = $itemname . " " . $count . "개";
		// }
		// $sender->sendMessage(
		// SMarket::$prefix . $recipeType . " 을(를) 통해 차익이 발생합니다 : "
		// . implode(", ", $arr) . "(구매가 " . $ingredientsBuyPrice . ") => " . Util::itemFullName($result) . "(판매가 " . $resultSellPrice . ")"
		//);
		// $invalid++;
		// }
		// }

		// $sender->sendMessage(SMarket::$prefix . "상점 가격표를 모두 점검하였습니다.(" .($invalid == 0 ? "이상 없음" : $invalid . "개 발견됨") . ")");
		// $sender->sendMessage(SMarket::$prefix . "* 주의!해당 명령어로는 완벽하게 점검하기가 힘듭니다.");
		// return true;
	}
}
