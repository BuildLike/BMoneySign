<?php

namespace BuildLike;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerJoinEvent;
use onebone\economyapi\EconomyAPI;

class BMoneySign extends PluginBase implements Listener {
	public $award, $aDB;
	public $tag = "§d[ §f보상 §d]";
	public function onEnable() {
		@mkdir ( $this->getDataFolder () );
		$this->award = new Config ( $this->getDataFolder () . "serverawards.yml", Config::YAML );
		$this->aDB = $this->award->getAll ();
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
	}
	public function sign(SignChangeEvent $event) {
		$player = $event->getPlayer ();
		$block = $event->getBlock ();
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		$name = $player->getName ();
		if ($event->getLine ( 0 ) == "보상") {
			if ($player->isOp ()) {
				if (! is_numeric ( $event->getLine ( 1 ) )) {
					$player->sendMessage ( $this->tag . "§l§d[1번째 라인] 보상표지판\n" . $this->tag . "§d[2번째 라인] 금액" );
					return false;
				}
				$this->makesign ( $x, $y, $z, $event->getLine ( 1 ) );
				$event->setLine ( 0, $this->tag );
				$event->setLine ( 1, "§d[ §f금액§d ] :§d " . $event->getLine ( 1 ) . "원" );
				$event->setLine ( 2, "§d[ §f터치시 보상 지급!§d ]" );
			}
		}
	}
	public function interact(PlayerInteractEvent $event) {
		$player = $event->getPlayer ();
		$name = strtolower ( $player->getName () );
		$block = $event->getBlock ();
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		if ($block->getId () == Block::SIGN_POST or $block->getId () == Block::WALL_SIGN) {
			if (isset ( $this->aDB [$x . ":" . $y . ":" . $z] )) {
				if (! isset ( $this->aDB [$x . ":" . $y . ":" . $z] [$name] ["cooldown"] )) {
					$player->sendMessage ("§l§d[ §f알림§d ] 보상을 지급 하였습니다!" );
					EconomyAPI::getInstance()->addMoney ( $player, $this->aDB [$x . ":" . $y . ":" . $z] ["money"] );
					$this->aDB [$x . ":" . $y . ":" . $z] [$name] ["cooldown"] = date ( "d" );
					$this->save ();
					return true;
				}
				if ($this->aDB [$x . ":" . $y . ":" . $z] [$name] ["cooldown"] != date ( "d" )) {
					$player->sendMessage ("§l§d[ §f알림§d ] §e: 보상을 지급 하였습니다!" );
					EconomyAPI::getInstance()->addMoney ( $player, $this->aDB [$x . ":" . $y . ":" . $z] ["money"] );
					$this->aDB [$x . ":" . $y . ":" . $z] [$name] ["cooldown"] = date ( "d" );
					$this->save ();
				} else {
					$player->sendMessage ( $this->tag . "§l§d[ §f알림§d ] §e: 24시간이 지난후에 다시 시도해주세요." );
				}
			}
		}
	}
	public function join(PlayerJoinEvent $event) {
		$player = $event->getPlayer ();
		$n = strtolower ( $player->getName () );
	}
	public function onbreak(BlockBreakEvent $event) {
		$player = $event->getPlayer ();
		$block = $event->getBlock ();
		$x = $block->x;
		$y = $block->y;
		$z = $block->z;
		if ($block->getId () == Block::SIGN_POST or $block->getId () == Block::WALL_SIGN) {
			if (isset ( $this->aDB [$x . ":" . $y . ":" . $z] )) {
				if ($player->isOp ()) {
					unset ( $this->aDB [$x . ":" . $y . ":" . $z] );
					$player->sendMessage ( $this->tag . " §l§d[ §f알림§d ] 표지판을 성공적으로 제거 하였습니다." );
				}
			}
		}
	}
	public function makesign($x, $y, $z, $money) {
		$this->aDB [$x . ":" . $y . ":" . $z] = [ ];
		$this->aDB [$x . ":" . $y . ":" . $z] ["money"] = $money;
		$this->save ();
	}
	public function save() {
		$this->award->setAll ( $this->aDB );
		$this->award->save ();
	}
}