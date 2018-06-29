<?php

namespace solo\smarket\validate;

/**
 * 조합 Validation을 위한 클래스입니다.
 */
class IngredientList{

  private $ingredients;
  private $divider = 1;

  public function __construct(array $ingredients = []){
    $this->ingredients = $ingredients;
  }

  public function getName(){
    $items = [];
    foreach($this->ingredients as $ingredient){
      $name = $ingredient->getName();
      $items[$name] =($items[$name] ?? 0) + 1;
    }
    $strings = [];
    foreach($items as $name => $count){
      $strings[] = $name . " " .($this->divider == 1 ? $count : $count . "/" . $this->divider) . "개";
    }
    return implode(", ", $strings);
    //return implode(", ", array_map(function($ingredient){return $ingredient->getName(); }, $this->ingredients)) .($this->divider == 1 ? "" : " [1/" . $this->divider . "]");
  }

  public function setDivider(int $divider){
    $this->divider = $divider;
  }

  public function getDivider(){
    return $this->divider;
  }

  public function getIngredients(){
    return $this->ingredients;
  }

  public function addIngredient(Ingredient $ingredient){
    $this->ingredients[] = $ingredient;
  }

  public function equals(IngredientList $list){
    $checkList1 = $list->getIngredients();
    $checkList2 = $this->getIngredients();
    foreach($checkList1 as $checkKey1 => $check1){
      foreach($checkList2 as $checkKey2 => $check2){
        if($check1->equals($check2)){
          unset($checkList1[$checkKey1]);
          unset($checkList2[$checkKey2]);
          break;
        }
      }
    }
    return count($checkList1) == 0 && count($checkList2) == 0;
  }

  public function getPrice(ItemList $checked = null){
    $total = 0;
    foreach($this->ingredients as $ingredient){
      $price = -1;
      if($ingredient instanceof Item){
        $price = $ingredient->getLowestPrice($checked);
      }else{
        $price = $ingredient->getPrice();
      }
      if($price < 0){
        return -1;
      }
      $total += $price;
    }
    return $total / $this->divider;
  }
}
