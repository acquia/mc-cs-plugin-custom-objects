<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemXrefEntityDiscoveryEvent extends Event
{
    private ?CustomItemXrefInterface $customItemXrefEntity = null;

    public function __construct(
        private CustomItem $customItem,
        private string $entityType,
        private int $entityId
    ) {
    }

    public function getCustomItem(): CustomItem
    {
        return $this->customItem;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setXrefEntity(CustomItemXrefInterface $customItemXrefEntity): void
    {
        $this->customItemXrefEntity = $customItemXrefEntity;
    }

    public function getXrefEntity(): ?CustomItemXrefInterface
    {
        return $this->customItemXrefEntity;
    }
}
