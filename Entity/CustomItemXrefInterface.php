<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use DateTimeInterface;

interface CustomItemXrefInterface
{
    /**
     * @return CustomItem
     */
    public function getCustomItem();

    /**
     * @return object
     */
    public function getLinkedEntity();

    /**
     * @return DateTimeInterface
     */
    public function getDateAdded();
}
