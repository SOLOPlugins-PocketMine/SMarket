<?php

namespace solo\smarket\command;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use solo\smarket\SMarket;
use solo\smarket\process\MarketCreateProcess;

class MarketCreateCommand extends Command {

    private $owner;

    public function __construct(SMarket $owner) {
        parent::__construct("상점생성", "손에 들고 있는 아이템으로 상점을 생성합니다.", "/상점생성");
        $this->setPermission("smarket.command.create");

        $this->owner = $owner;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(SMarket::$prefix . "인게임에서만 사용할 수 있습니다.");
            return true;
        }
        if (!$sender->hasPermission($this->getPermission())) {
            $sender->sendMessage(SMarket::$prefix . "이 명령을 실행할 권한이 없습니다.");
            return true;
        }
        if ($this->owner->getProcessManager()->getProcess($sender) instanceof MarketCreateProcess) {
            $this->owner->getProcessManager()->removeProcess($sender);
            $sender->sendMessage(SMarket::$prefix . "상점생성 작업을 중단합니다.");
            return true;
        }
        $this->owner->getProcessManager()->setProcess($sender, new MarketCreateProcess($sender));
        $sender->sendMessage(SMarket::$prefix . "/상점생성 명령어를 한번 더 입력하면 상점생성을 중단합니다.");
        return true;
    }
}
