<?php
namespace RandomMotd;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
class RandomMotd extends PluginBase {
	public $motd;
	public function onEnable() {
		@mkdir($this->getDataFolder());
		$this->motd = new Motd($this);
		$this->motd->loadList();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new MotdChangeTask($this), 20);
	}
	public function onDisable() {
		$this->motd->save();
	}
	public function onCommand(CommandSender $sender, Command $command, $label, Array $args) {
		if (!isset($args[0])) {
			return false;
		}
		switch ($args[0]) {
			case 'add' :
				if(!isset($args[1])) {
					$sender->sendMessage('사용법: /rmotd add <메세지>');
					break;
				}
				array_shift($args);
				$message = implode(' ', $args);
				$this->motd->addMotd($message);
				$sender->sendMessage("motd {$message} 를 추가했습니다.");
				break;
			case 'delete' :
				if (!isset($args[1])) {
					$sender->sendMessage('사용법: /rmotd delete <번호>');
					break;
				}
				if ($this->motd->deleteMotd($args[1] - 1)) {
					$sender->sendMessage('성공적으로 해당 motd 를 제거했습니다.');
				} else {
					$sender->sendMessage('해당 번호의 motd 가 존재하지 않습니다.');
				}
				break;
			case 'list' :
				if (!isset($args[1])) {
					$sender->sendMessage('페이지1 :');
					for ($i = 0; $i < 5; $i++) {
						if (isset($this->motd->getMotdList()[$i]))
							$sender->sendMessage( "[".(string)($i + 1) . "] " . $this->motd->getMotdList()[$i]);
					}
					break;
				}
				if (!is_numeric($args[1])) {
					$sender->sendMessage('도움말: /rmotd list '.TextFormat::RED.'<숫자>');
					break;
				}
				$sender->sendMessage('페이지'. $args[1] . ':');
				for ($i = $args[1] * 5 - 5; $i < $args[1] * 5; $i++) {
					if (isset($this->motd->getMotdList()[$i]))
						$sender->sendMessage("[".(string)($i+1) ."]".$this->motd->getMotdList()[$i]);
				}
				break;
			default :
				return false;
		}
		return true;
	}
}
class Motd {
	public $plugin;
	public $motdlist;
	public function __construct(RandomMotd $plugin) {
		$this->plugin = $plugin;
	}
	public function addMotd($motd) {
		array_push($this->motdlist, $motd);
	}
	public function getMotdList() {
		return $this->motdlist;
	}
	public function deleteMotd($index) {
		if(isset($this->motdlist[$index])) {
			unset($this->motdlist[$index]);
			return true;
		} else {
			return false;
		}
	}
	public function loadList() {
		$this->motdlist = (new Config($this->plugin->getDataFolder().'motdlist.json', Config::JSON, []))->getAll();
	}
	public function save() {
		$motdlist = new Config($this->plugin->getDataFolder().'motdlist.json', Config::JSON);
		$motdlist->setAll($this->motdlist);
		$motdlist->save();
	}
}
class MotdChangeTask extends PluginTask {
	public $plugin;
	public function __construct(RandomMotd $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}
	public function onRun($currentTick) {
		$this->plugin->getServer()->getNetwork()->setName($this->rand_array($this->plugin->motd->getMotdList()));
	}
	public function rand_array(Array $args) {
		if (count($args) == 0) {
			return $this->plugin->getServer()->getMotd();
		} else {
			return $args[array_rand($args)];
		}
	}
}
?>