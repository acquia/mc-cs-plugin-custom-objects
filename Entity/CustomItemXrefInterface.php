<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

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
     * @return \DateTimeInterface
     */
    public function getDateAdded();
}
