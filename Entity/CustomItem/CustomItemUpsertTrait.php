<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

trait CustomItemUpsertTrait
{
    public function wasInserted(): bool
    {
        return $this->wasInserted;
    }

    public function wasUpdated(): bool
    {
        return $this->wasUpdated;
    }

    public function setWasInserted(bool $wasInserted): void
    {
        $this->wasInserted = $wasInserted;
    }

    public function setWasUpdated(bool $wasUpdated): void
    {
        $this->wasUpdated = $wasUpdated;
    }

}
