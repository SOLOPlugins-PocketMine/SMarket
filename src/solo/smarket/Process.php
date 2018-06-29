<?php

namespace solo\smarket;

use pocketmine\Player;
use pocketmine\event\player\PlayerInteractEvent;

abstract class Process{

  protected $player;

  public function __construct(Player $player){
    $this->player = $player;
  }

  abstract public function getName();

  public function handlePlayerInteract(PlayerInteractEvent $event){

  }
}
