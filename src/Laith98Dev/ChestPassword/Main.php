<?php

declare(strict_types=1);

namespace Laith98Dev\ChestPassword;

/*  
 *  A plugin for PocketMine-MP.
 *  
 *	 _           _ _   _    ___   ___  _____             
 *	| |         (_) | | |  / _ \ / _ \|  __ \            
 *	| |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *	| |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *	| |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *	|______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *	
 *	Copyright (C) 2021 Laith98Dev
 *  
 *	Youtube: Laith Youtuber
 *	Discord: Laith98Dev#0695
 *	Gihhub: Laith98Dev
 *	Email: help@laithdev.tk
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 	
 */

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\Player;
use pocketmine\block\Block;

use pocketmine\tile\Chest;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;

class Main extends PluginBase implements Listener {
	
	/** @var string[] */
	public $placeSave = [];
	
	/** @var string[] */
	public $quee = [];
	
	/** @var string[] */
	public $canBreakChest = [];
	
	public function onEnable(): void{		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		if(!is_file($this->getDataFolder() . "data.yml")){
			(new Config($this->getDataFolder() . "data.yml", Config::YAML, ["all_chests" => []]));
		}
	}
	
	public function onInteract(PlayerInteractEvent $event): void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK || $block->getId() !== Block::CHEST || !is_file($this->getDataFolder() . "data.yml"))
			return;
		
		$data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
		$all = $data->get("all_chests", []);
		
		$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
		
		foreach ($all as $p){
			foreach ($p as $pos => $pp){
				if($pos == $key){
					$passAndOwner = explode("_", $pp);
					// if($passAndOwner[1] == $player->getName())
						// break;
					
					$event->cancel();
					$this->OpenPasswordForm($player, $block);
				}
			}
		}
		
		$tile = $block->getLevel()->getTile($block);
		if(($pair = $tile->getPair()) !== null){
			$key = (int)$pair->getFloorX() . "_" . (int)$pair->getFloorY() . "_" . (int)$pair->getFloorZ();
			foreach ($all as $p){
				foreach ($p as $pos => $pp){
					if($pos == $key){
						$passAndOwner = explode("_", $pp);
						if($passAndOwner[1] == $player->getName())
							break;
						
						$event->cancel();
						$this->OpenPasswordForm($player, $block);
					}
				}
			}
		}
	}
	
	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		
		if($block->getId() == Block::CHEST){
			$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
			
			$this->placeSave[$player->getName()] = $key;
		}
	}
	
	public function onBreak(BlockBreakEvent $event): void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
		
		if($this->canBreak($block))
			return;
		
		if(!$this->checkChest($block)){
			if(isset($this->placeSave[$player->getName()])){
				$skey = $this->placeSave[$player->getName()];
				if($skey == $key){
					$event->cancel();
					$this->OpenNewChestForm($player, $block);
				}
			}
			return;
		}
		
		if($this->isChestOwner($player, $block)){
			$event->cancel();
			$this->OpenEditForm($player, $block);
		} else {
			$player->sendMessage("you can't break this chest because have a password");
			$event->cancel();
		}
	}
	
	public function canBreak($block){
		$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
		return isset($this->canBreakChest[$key]) && $this->canBreakChest[$key] == true;
	}
	
	public function setCanBreak($block, bool $val = true){
		$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
		$this->canBreakChest[$key] = $val;
	}
	
	public function checkChest(Block $block): bool{
		if($block->getId() !== Block::CHEST)
			return false;
		$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
		$data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
		$all = $data->get("all_chests", []);
		foreach ($all as $p){
			foreach ($p as $pos => $pp){
				if($pos == $key){
					return true;
				}
			}
		}
		
		$tile = $block->getLevel()->getTile($block);
		if(($pair = $tile->getPair()) !== null){
			$key = (int)$pair->getFloorX() . "_" . (int)$pair->getFloorY() . "_" . (int)$pair->getFloorZ();
			foreach ($all as $p){
				foreach ($p as $pos => $pp){
					if($pos == $key){
						return true;
					}
				}
			}
		}
		
		return false;
	}
	
	public function OpenChest(Player $player, Block $block){
		if($block->getId() !== Block::CHEST)
			return false;
		
		$chestInv = $block->getLevel()->getTile($block)->getInventory();
		$player->addWindow($chestInv);
	}
	
	public function checkPassword(string $pass, Block $block): bool{
		if($block->getId() !== Block::CHEST)
			return false;
		$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
		$data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
		$all = $data->get("all_chests", []);
		foreach ($all as $p){
			foreach ($p as $pos => $pp){
				if($pos == $key){
					$passAndOwner = explode("_", $pp);
					if($passAndOwner[0] == $pass){
						return true;
					}
				}
			}
		}
		foreach ($all as $p){
			foreach ($p as $pos => $pp){
				if($pos == $key){
					$passAndOwner = explode("_", $pp);
					if($passAndOwner[0] == $pass){
						return true;
					}
				}
			}
		}
		
		$tile = $block->getLevel()->getTile($block);
		if(($pair = $tile->getPair()) !== null){
			$key = (int)$pair->getFloorX() . "_" . (int)$pair->getFloorY() . "_" . (int)$pair->getFloorZ();
			foreach ($all as $p){
				foreach ($p as $pos => $pp){
					if($pos == $key){
						$passAndOwner = explode("_", $pp);
						if($passAndOwner[0] == $pass){
							return true;
						}
					}
				}
			}
		}
		
		return false;
	}
	
	public function isChestOwner(Player $player, Block $block): bool{
		if($block->getId() !== Block::CHEST)
			return false;
		$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
		$data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
		$all = $data->get("all_chests", []);
		foreach ($all as $p){
			foreach ($p as $pos => $pp){
				if($pos == $key){
					$passAndOwner = explode("_", $pp);
					if($passAndOwner[1] == $player->getName()){
						return true;
					}
				}
			}
		}
		
		$tile = $block->getLevel()->getTile($block);
		if(($pair = $tile->getPair()) !== null){
			$key = (int)$pair->getFloorX() . "_" . (int)$pair->getFloorY() . "_" . (int)$pair->getFloorZ();
			foreach ($all as $p){
				foreach ($p as $pos => $pp){
					if($pos == $key){
						$passAndOwner = explode("_", $pp);
						if($passAndOwner[1] == $player->getName()){
							return true;
						}
					}
				}
			}
		}
		
		return false;
	}
	
	public function OpenPasswordForm(Player $player, Block $block){
		$this->quee[$player->getName()] = $block;
		$form = new CustomForm(function (Player $player, array $data = null){
			$result = $data;
			if($result === null)
				return false;
			
			$block = $this->quee[$player->getName()];
			$pass = null;
			if($data[0] !== null)
				$pass = $data[0];
			
			if($pass == null)
				return false;
			
			if($this->checkPassword($pass, $block)){
				$this->OpenChest($player, $block);
			} else {
				$player->sendMessage(TF::RED . "Incorrect Password");
			}
		});
		
		$form->setTitle("ChestPassword");
		$form->addInput("Password:");
		$player->sendForm($form);
	}
	
	public function OpenNewChestForm(Player $player, Block $block){
		$this->quee[$player->getName()] = $block;
		$form = new ModalForm(function (Player $player, $data = null){
			$result = $data;
			if($result === null)
				return false;
			
			switch ($result){
				case 1:
					$block = $this->quee[$player->getName()];
					$this->OpenSetPasswordForm($player, $block);
				break;
				
				case 2:
					$block = $this->quee[$player->getName()];
					$level = $block->getLevel();
					$this->setCanBreak($block, true);
					$level->useBreakOn($block);
				break;
			}
		});
		
		$form->setTitle("New Chest");
		$form->setContent("do you need set password to this chest?");
		$form->setButton1("Yes");
		$form->setButton2("No");
		$player->sendForm($form);
	}
	
	public function OpenSetPasswordForm(Player $player, Block $block){
		$this->quee[$player->getName()] = $block;
		$form = new CustomForm(function (Player $player, array $data = null){
			$result = $data;
			if($result === null)
				return false;
			
			$block = $this->quee[$player->getName()];
			$pass = null;
			if($data[0] !== null)
				$pass = $data[0];
			
			if($pass == null)
				return false;
			
			if(strpos("_", $pass) !== false){
				$player->sendMessage(TF::RED . "You cant use '_' in password");
				return false;
			}
			
			$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
			$data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
			$all = $data->get("all_chests", []);
			$all[$player->getName()][][$key] = $pass . "_" . $player->getName();
			$data->set("all_chests", $all);
			$data->save();
			
			$player->sendMessage("Ssuccessfully add password!");
		});
		
		$form->setTitle("New Chest");
		$form->addInput("Password");
		$player->sendForm($form);
	}
	
	public function OpenEditForm(Player $player, Block $block){
		$this->quee[$player->getName()] = $block;
		$form = new SimpleForm(function (Player $player, int $data = null){
			$result = $data;
			if($result === null)
				return false;
			
			$block = $this->quee[$player->getName()];
			switch ($result){
				case 0:
					$this->OpenChangePasswordForm($player, $block);
				break;
				
				case 1:
					$this->deletePasswordConfirm($player, $block);
				break;
			}
		});
		
		$form->setTitle("Edit Chest");
		$form->addButton("Change Password");
		$form->addButton("Delete Password");
		$player->sendForm($form);
	}
	
	public function OpenChangePasswordForm(Player $player, Block $block){
		if(!isset($this->quee[$player->getName()]))
			$this->quee[$player->getName()] = $block;
		
		$form = new CustomForm(function (Player $player, array $data = null){
			$result = $data;
			if($result === null)
				return false;
			
			$newPass = null;
			if($data[0] !== null)
				$newPass = $data[0];
			
			if($newPass == null)
				return false;
			
			if(strpos("_", $newPass) !== false){
				$player->sendMessage(TF::RED . "You cant use '_' in password");
				return false;
			}
			
			$block = $this->quee[$player->getName()];
			$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
			$data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
			$all = $data->get("all_chests", []);
			$all[$player->getName()][][$key] = $newPass . "_" . $player->getName();
			$data->set("all_chests", $all);
			$data->save();
			
			$player->sendMessage("Ssuccessfully changed the password!");
		});
		
		$form->setTitle("Change Password");
		$form->addInput("New Password: ");
		$player->sendForm($form);
	}
	
	public function deletePasswordConfirm(Player $player, Block $block){
		$this->quee[$player->getName()] = $block;
		$form = new ModalForm(function (Player $player, $data = null){
			$result = $data;
			if($result === null)
				return false;
			
			switch ($result){
				case 1:
					$block = $this->quee[$player->getName()];
					$key = (int)$block->getFloorX() . "_" . (int)$block->getFloorY() . "_" . (int)$block->getFloorZ();
					$data = new Config($this->getDataFolder() . "data.yml", Config::YAML);
					$all = $data->get("all_chests", []);
					foreach ($all as $pp => $data_){
						foreach ($data_ as $k => $po){
							$passAndOwner = explode("_", $po);
							if($passAndOwner[1] == $player->getName()){
								if($k == $key){
									unset($all[$pp][$key]);
								}
							}
						}
					}
					
					if(($pair = $tile->getPair()) !== null){
						$key = (int)$pair->getFloorX() . "_" . (int)$pair->getFloorY() . "_" . (int)$pair->getFloorZ();
						foreach ($all as $pp => $data_){
							foreach ($data_ as $k => $po){
								$passAndOwner = explode("_", $po);
								if($passAndOwner[1] == $player->getName()){
									if($k == $key){
										unset($all[$pp][$key]);
									}
								}
							}
						}
					}
					
					$data->set("all_chests", $all);
					$data->save();
					
					$player->sendMessage("Password has been deleted!");
				break;
				
				case 2:
					
				break;
			}
		});
		
		$form->setTitle("New Chest");
		$form->setContent("Are you sure to delete the password?");
		$form->setButton1("Yes");
		$form->setButton2("No");
		$player->sendForm($form);
	}
}
