<?php

namespace solo\smarket\task;

use pocketmine\scheduler\Task;
use solo\smarket\SMarket;

class SaveTask extends Task {

    private $owner;

    public function __construct(SMarket $owner) {
        $this->owner = $owner;
    }

    public function onRun(int $currentTick) {
        $this->owner->getMarketFactory()->save();
        $this->owner->getMarketManager()->save();
    }
}
