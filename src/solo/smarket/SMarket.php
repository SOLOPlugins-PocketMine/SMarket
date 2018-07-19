<?php

namespace solo\smarket;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\item\Item;
use pocketmine\utils\Config;

use solo\smarket\task\SaveTask;
use solo\smarket\validate\Validator;
use solo\smarket\validate\BuySellValidator;
use solo\smarket\validate\RecipeValidator;

class SMarket extends PluginBase{

	public static $prefix = "§l§b[SMarket]§r§7 ";

	private static $instance = null;

	public static function getInstance(){
		return self::$instance;
	}

	/** @var MarketFactory */
	private $marketFactory = null;

	/** @var MarketManager */
	private $marketManager = null;

	/** @var ProcessManager */
	private $processManager;

	/** @var Validator[] */
	private $validatorList = [];

	/** @var EconomyAPI */
	private $economy;

	/** @var Config */
	private $setting;

	public function onLoad(){
		if(self::$instance !== null){
			throw new \InvalidStateException();
		}
		self::$instance = $this;
	}

	public function onEnable(){
		// dependency check
		if(($this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")) === null){
			$this->getServer()->getLogger()->critical("[SMarket] EconomyAPI 플러그인이 없습니다.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		@mkdir($this->getDataFolder());
		$this->saveResource("setting.yml");
		$this->setting = new Config($this->getDataFolder() . "setting.yml", Config::YAML);

		$this->marketFactory = new MarketFactory($this);
		$this->marketManager = new MarketManager($this);
		$this->processManager = new ProcessManager($this);

		$this->validatorList[] = new RecipeValidator($this);
		$this->validatorList[] = new BuySellValidator($this);

		foreach([
			"BuyCommand",
			"BuyPriceCommand",
			"MarketCreateCommand",
			"MarketValidateCommand",
			"SellCommand",
			"SellPriceCommand"
		] as $class){
			$class = "\\solo\\smarket\\command\\" . $class;
			$this->getServer()->getCommandMap()->register("smarket", new $class($this));
		}

		$this->getScheduler()->scheduleRepeatingTask(new SaveTask($this), 14800);
	}

	public function onDisable(){
		if($this->marketFactory !== null){
			$this->marketFactory->save();
		}
		if($this->marketManager !== null){
			$this->marketManager->save();
		}

		self::$instance = null;
	}

	public function getMarketFactory(){
		return $this->marketFactory;
	}

	public function getMarketManager(){
		return $this->marketManager;
	}

	public function getProcessManager(){
		return $this->processManager;
	}

	public function getAllValidator(){
		return $this->validatorList;
	}

	public function getEconomyAPI(){
		return $this->economy;
	}

	public function getSetting(){
		return $this->setting;
	}
}
