<?php

declare(strict_types=1);

namespace FlyPlugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;

class Main extends PluginBase implements Listener {
    
    private array $flyingPlayers = [];
    
    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) return false;
        
        if (strtolower($command->getName()) === "fly") {
            $this->toggleFly($sender);
            return true;
        }
        
        return false;
    }
    
    private function toggleFly(Player $player): void {
        if ($player->getAllowFlight()) {
            $player->setAllowFlight(false);
            $player->setFlying(false);
            $player->sendMessage("§cFly disabled");
            unset($this->flyingPlayers[$player->getName()]);
            $player->getEffects()->remove(VanillaEffects::SPEED());
        } else {
            $player->setAllowFlight(true);
            $player->setFlying(true);
            $player->sendMessage("§aFly enabled");
            $this->flyingPlayers[$player->getName()] = true;
            $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999, 1, false));
            $player->getWorld()->addSound($player->getPosition(), new NoteSound(NoteInstrument::HARP(), 20));
            $this->startBoneMealRain($player);
        }
    }
    
    private function startBoneMealRain(Player $player): void {
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($player): void {
            if (!$player->isOnline() || !isset($this->flyingPlayers[$player->getName()]) || !$player->isFlying()) {
                return;
            }
            
            $world = $player->getWorld();
            $pos = $player->getPosition();
            
            for ($i = 0; $i < 3; $i++) {
                $boneMeal = VanillaItems::BONE_MEAL();
                $world->dropItem($pos->add(mt_rand(-2, 2), mt_rand(1, 3), mt_rand(-2, 2)), $boneMeal);
            }
            
            $world->addParticle($pos->add(0, 2, 0), new FloatingTextParticle("§a✨"));
        }), 40);
    }
    
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        
        if (isset($this->flyingPlayers[$player->getName()]) && $player->isFlying()) {
            if (mt_rand(1, 10) === 1) {
                $player->getWorld()->addParticle($player->getPosition()->add(0, 1, 0), new FloatingTextParticle("§b⭐"));
            }
        }
    }
}
