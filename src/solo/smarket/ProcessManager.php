<?php

namespace solo\smarket;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;

class ProcessManager implements Listener{

	private $owner;

	private $processList = [];

	public function __construct(SMarket $owner){
		$this->owner = $owner;

		$this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
	}

	public function getProcess(Player $player){
		return $this->processList[$player->getName()] ?? null;
	}

	public function setProcess(Player $player, Process $process){
		$this->processList[$player->getName()] = $process;
	}

	public function removeProcess(Player $player){
		unset($this->processList[$player->getName()]);
	}

	/**
	 * @priority NORMAL
	 *
	 * @ignoreCancelled true
	 */
	public function handlePlayerInteract(PlayerInteractEvent $event){
		$process = $this->getProcess($event->getPlayer());
		if($process !== null){
			$process->handlePlayerInteract($event);
		}
	}

	public function handlePlayerQuit(PlayerQuitEvent $event){
		$this->removeProcess($event->getPlayer());
	}
}
