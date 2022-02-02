<?php

declare(strict_types=1);

namespace tatchan\DirectlyInventoryWithDropItem;

use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\player\Player;
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
            var_dump(array_diff($beforeDrops, $afterDrops));
            $realDrops = array_diff($beforeDrops, $afterDrops);
            $realDropItems = array_map(function (ItemEntity $entity): Item {
                return $entity->getItem();
            }, $realDrops);
            $this->dropItemInternal($player, ...$realDropItems);
        }), 0);
    }

    private function dropItemInternal(Player $player, Item ...$drops): void {
        if (count($player->getInventory()->addItem(...$drops)) > 0) {
            foreach ($drops as $drop) {
                $player->getWorld()->dropItem($player->getPosition()->add(0, 10, 0), $drop);
            }
        }
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
