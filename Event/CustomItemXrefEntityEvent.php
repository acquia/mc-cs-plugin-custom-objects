<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CustomItemXrefEntityEvent extends Event
{
    /**
     * @var CustomItemXrefInterface
     */
    private $xRef;

    public function __construct(CustomItemXrefInterface $xRef)
    {
        $this->xRef = $xRef;
    }

    public function getXref(): CustomItemXrefInterface
    {
        return $this->xRef;
    }
}
