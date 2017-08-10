<?php

/**
 * @author matcracker
 * Plugin for PocketMine
 * Version 1.1
 * API: 3.0.0-ALPHA6, 3.0.0-ALPHA7
 */

declare(strict_types = 1);

namespace matcracker\PMManagerServers;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat as C;

class Task extends PluginTask{
    private $plugin;
    private $sender;
    
    public function __construct(Main $instance, CommandSender $sender){
        parent::__construct($instance);
        $this->plugin = $instance;
        $this->sender = $sender;
    }
    
    public function onRun(int $currentTick){
        $pfx = $this->plugin->getPrefix();
        
        $this->plugin->debugMessage("[Spam] Checking for commands...");
        if(count($this->plugin->getCommands()) > 0 || $this->plugin->isOverrided()){
            foreach($this->plugin->getCommands() as $cmd) {
                $this->plugin->debugMessage("[Spam] Found! Executing /" . $cmd . "...");
                $this->getOwner()->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                unlink($this->plugin->getDataFolder() . $cmd);
                $this->sender->sendMessage($pfx . C::GREEN . "Executed command: " . $cmd);
            }
        }else{
            $this->sender->sendMessage($pfx . C::RED . "Any commands to be executed");
            $this->plugin->getServer()->getScheduler()->cancelTasks($this->plugin);
        }
        $this->plugin->debugMessage("[Spam] No commands found. Try to re-check others...");
    }
    
}