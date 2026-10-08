<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemEvent extends Event
{
    /**
     * @var CustomItem
     */
    private $customItem;

    /**
     * @var bool
     */
    private $isNew;

    public function __construct(CustomItem $customItem, bool $isNew = false)
    {
        $this->customItem = $customItem;
        $this->isNew      = $isNew;
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
