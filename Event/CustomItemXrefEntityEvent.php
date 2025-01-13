<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemXrefEntityEvent extends Event
{
    public function __construct(private CustomItemXrefInterface $xRef)
    {
    }

    public function getXref(): CustomItemXrefInterface
    {
        return $this->xRef;
    }
}
