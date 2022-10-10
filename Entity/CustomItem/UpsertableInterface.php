<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

interface UpsertableInterface
{
    public function wasInserted(): bool;

    public function wasUpdated(): bool;

    public function setWasUpdated(bool $wasUpdated): void;

    public function setWasInserted(bool $wasInserted): void;
}
