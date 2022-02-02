<?php

declare(strict_types=1);

namespace tatchan\DirectlyInventoryWithDropItem;

use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\World;

class EventListener implements Listener
{
    public function __construct(private Main $main) {
    }

    public function sendDropsToInventory(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $world = $event->getBlock()->getPosition()->getWorld();
        $beforeDrops = $this->getFilteredEntities($world, ItemEntity::class);

        $this->main->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $world, $beforeDrops): void {
            $afterDrops = $this->getFilteredEntities($world, ItemEntity::class);
            $realDrops = array_diff($afterDrops, $beforeDrops);
            foreach ($realDrops as $realDrop) {
                $inventory = $player->getInventory();
                $realDropItem = $realDrop->getItem();
                if ($inventory->canAddItem($realDropItem)) {
                    $inventory->addItem($realDropItem);
                    $realDrop->kill();
                }
            }
        }), 2);
    }

    /**
     * @return Entity[]
     */
    private function getFilteredEntities(World $world, string $entityType = Entity::class): array {
        assert(is_a($entityType, Entity::class, true));

        $entities = [];
        foreach ($world->getEntities() as $entity) {
            if ($entity instanceof $entityType) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }
}
