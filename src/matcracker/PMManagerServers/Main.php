<?php
/**
 * @author matcracker
 * Plugin for PocketMine
 * Version 1.0
 * API: 2.0.0
 */

namespace matcracker\PMManagerServers;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as C;

class Main extends PluginBase{
    const pfx = C::DARK_AQUA . "[PMMS] " . C::RESET;
    private $taskStatus = false;

    public function onEnable(){
        if(!file_exists($this->getDataFolder() . "config.yml")){
            @mkdir($this->getDataFolder());
            $this->saveDefaultConfig();
        }

        if($this->getConfig()->get("enable-onStart") === true){
            $this->getLogger()->info(self::pfx . C::LIGHT_PURPLE . "Enabling command executor...");
            
            $task = new Task($this, new ConsoleCommandSender());
            $ticks = $this->getTime();

            if($task != null && $ticks >= 0 && $this->taskStatus == false){
                $this->getServer()->getScheduler()->scheduleRepeatingTask($task, ($ticks * 20));
                $this->debugMessage("Starting schedule...");
                $this->taskStatus = true;
            }else{
                $this->getLogger()->info($this->getPrefix() . C::RED . "ERROR! Ticks can't be negative or you already start a schedule!");
            }
        }
        
        $this->getLogger()->info(C::GREEN . "PM-ManagerServers enabled successfully!");
    }

    public function onDisable(){
        $this->getLogger()->info(C::GREEN . "PM-ManagerServers disabled successfully!");
    }

    public function onCommand(CommandSender $sender, Command $command, $lbl, array $args){
        $cmd = strtolower($command->getName());
        if($cmd === "pmms"){
            if(!isset($args[0]) || empty($args[0]))
                $args[0] = "help";

            switch(strtolower($args[0])){
                case "h":
                case "help":
                    if($sender->hasPermission("pmms.command.help")){
                        $sender->sendMessage(C::AQUA . "========<PocketMine-ManagerServers>========");
                        $sender->sendMessage("§d/pmms help§f: List of commands");
                        $sender->sendMessage("§d/pmms start §o[seconds] [override(true/false)]§r§f: Start the schedule for execute commands from plugin's directory");
                        $sender->sendMessage("§d/pmms stop§f: Stop the service of commands");
                        $sender->sendMessage("§d/pmms queue§f: Show list of commands that will be executed");
                        $sender->sendMessage("§d/pmms debug§f: Enable/Disable debug messages of plugin");
                        $sender->sendMessage("§d/pmms status§f: Show the status of plugin");
                        $sender->sendMessage("§d/pmms reload§f: Reload configuration");
                        $sender->sendMessage("§d/pmms info§f: Show information of plugin");
                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                case "start":
                    if($sender->hasPermission("pmms.command.start")){
                        
                        $ticks = $this->getTime();
                        $force = $this->isOverrided();

                        if(isset($args[1]) || !empty($args[1])){
                            $ticks = $args[1];
                        }

                        if(isset($args[2]) || !empty($args[2])){
                            $force = $args[2];
                        }
                        
                        $this->setTime($ticks);
                        $this->setOverride($force);

                        $task = new Task($this, $sender);

                        if($task != null && $ticks >= 0 && $this->taskStatus == false){
                            $this->getServer()->getScheduler()->scheduleRepeatingTask($task, ($ticks * 20));
                            $sender->sendMessage("Starting schedule...");
                            $this->taskStatus = true;
                        }else{
                            $sender->sendMessage($this->getPrefix() . C::RED . "ERROR! Ticks can't be negative or you already start a schedule!");
                        }
                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                case "stop":
                    if($sender->hasPermission("pmms.command.stop")){
                        if($this->taskStatus == true){
                            $this->getServer()->getScheduler()->cancelTasks($this);
                            $this->taskStatus = false;
                            $sender->sendMessage($this->getPrefix() . C::GREEN . "Schedule stopped!");
                        }else{
                            $sender->sendMessage($this->getPrefix() . C::RED . "The schedule is not running!");
                        }
                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                case "debug":
                    if($sender->hasPermission("pmms.command.debug")){
                        $debug = $this->isDebug();
                        
                        if(!$debug){
                            $this->setDebug(true);
                            $sender->sendMessage($this->getPrefix() . C::GRAY . C::ITALIC . "[Debug enabled]");
                        }else{
                            $this->setDebug(false);
                            $sender->sendMessage($this->getPrefix() . C::GRAY . C::ITALIC . "[Debug disabled]");
                        }
                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                case "status":
                    if($sender->hasPermission("pmms.command.status")){
                        $onStart = $this->getConfig()->get("enable-onStart");
                        $status = [
                            0 => C::RED . "Disabled",
                            1 => C::RED . "Disabled",
                            2 => C::RED . "Disabled",
                            3 => C::RED . "Disabled"
                        ];

                        if($this->taskStatus)
                            $status[0] = C::GREEN . "Enabled";
                        if($onStart)
                            $status[1] = C::GREEN . "Enabled";
                        if($this->isOverrided())
                            $status[2] = C::GREEN . "Enabled";
                        if($this->isDebug())
                            $status[3] = C::GREEN . "Enabled";

                        $sender->sendMessage(C::AQUA . "========<PocketMine-ManagerServers>========");
                        $sender->sendMessage("Schedule: " . $status[0]);
                        $sender->sendMessage("Time: " . $this->getTime());
                        $sender->sendMessage("Enabled when start: " . $status[1]);
                        $sender->sendMessage("Override: " . $status[2]);
                        $sender->sendMessage("Debug: " . $status[3]);
                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                case "queue":
                    if($sender->hasPermission("pmms.command.queue")){
                        $sender->sendMessage($this->getPrefix() . C::ITALIC . C::GREEN. "List of commands in queue:");
                        if(empty($this->getCommands())){
                            $sender->sendMessage(C::RED . "No commands in queue.");
                        }else{
                            foreach($this->getCommands() as $cmnd){
                                $sender->sendMessage("- " . $cmnd);
                            }
                        }

                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                case "reload":
                    if($sender->hasPermission("pmms.command.reload")){
                        $this->getConfig()->save();
                        $this->getConfig()->reload();
                        $this->saveDefaultConfig();
                        
                        $sender->sendMessage(C::GREEN . "Configuration reloaded successfully!");
                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                case "info":
                    if($sender->hasPermission("pmms.command.info")){
                        $sender->sendMessage(C::AQUA . "======<PocketMine-ManagerServers>======");
                        $sender->sendMessage("This plugin is made for PocketMine-ManagerServers software");
                        $sender->sendMessage("If you haven't PocketMine-ManagerServers software, this plugin");
                        $sender->sendMessage("is useless. You can download PM-MS from GitHub, search it!");
                        $sender->sendMessage("Plugin version: " . $this->getDescription()->getVersion());
                        $sender->sendMessage("Author: matcracker");
                    }else{
                        $sender->sendMessage(C::RED . "You don't have permission to use this command!");
                    }
                    break;
                default:
                    $sender->sendMessage($this->getPrefix() .  C::RED . "Use /pmms help for the list of commands");
            }
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getCommands(){
        $dir = $this->getDataFolder();
        $result = [];
        $c = 0;
        if(is_dir($dir)){
            if($directory_handle = opendir($dir)){
                while(($file = readdir($directory_handle)) !== false){
                    if((!is_dir($file)) & ($file!=".") & ($file!= "..") & ($file != "config.yml")){
                        $str = $file;
                        $this->debugMessage("Found file...");
                        $result[$c] = $str;
                        ++$c;
                    }
                }
                closedir($directory_handle);
            }
        }
        return $result;
    }
    
    public function getPrefix(){
        return self::pfx;
    }
    
    /**
     * @return int
     */
    public function getTime(){
        return (int) $this->getConfig()->get("time");
    }

    /**
     * @param int $time
     */
    public function setTime(int $time){
        $this->getConfig()->set("time", $time);
        $this->saveConfig();
    }

    /**
     * @return bool
     */
    public function isOverrided(){
        return $this->getConfig()->get("override");
    }

    /**
     * @param $override
     */
    public function setOverride($override){
        $this->getConfig()->set("override", $override);
        $this->saveConfig();
    }

    /**
     * @return bool
     */
    public function isDebug(){
        return $this->getConfig()->get("debug");
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug){
        $this->getConfig()->set("debug", $debug);
        $this->saveConfig();
    }
    
    /**
     * @param string $message
     */
    public function debugMessage(string $message){
        if($this->isDebug()){
            $this->getLogger()->info($this->getPrefix() . C::ITALIC . C::GRAY . $message);
        }
    }



}




