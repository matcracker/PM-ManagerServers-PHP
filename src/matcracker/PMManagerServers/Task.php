<?php

/**
 * @author matcracker
 * Plugin for PocketMine
 * Version 1.0
 * API: 2.0.0
 */
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
    
    public function onRun($ticks){
        $pfx = $this->plugin->getPrefix();
        
        $this->plugin->debugMessage("[Spam] Checking for commands...", $this->sender);
        if(count($this->plugin->getCommands()) > 0 || $this->plugin->isOverrided()){
            foreach($this->plugin->getCommands() as $cmd) {
                $this->getOwner()->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                unlink($this->plugin->getDataFolder() . $cmd);
                $this->sender->sendMessage($pfx . C::GREEN . "Executed command: " . $cmd);
            }
        }else{
            $this->sender->sendMessage($pfx . C::RED . "Any commands to be executed");
            $this->plugin->getServer()->getScheduler()->cancelTasks($this->plugin);
        }
    }
    
}