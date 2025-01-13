<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemEvent extends Event
{
    public function __construct(private CustomItem $customItem, private bool $isNew = false)
    {
    }

    public function getCustomItem(): CustomItem
    {
        return $this->customItem;
    }

    public function entityIsNew(): bool
    {
        return $this->isNew;
    }
}
