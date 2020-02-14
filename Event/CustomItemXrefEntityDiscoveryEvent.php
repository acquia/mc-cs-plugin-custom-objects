<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use Symfony\Component\EventDispatcher\Event;

class CustomItemXrefEntityDiscoveryEvent extends Event
{
    /**
     * @var CustomItem
     */
    private $customItem;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var int
     */
    private $entityId;

    /**
     * @var CustomItemXrefInterface|null
     */
    private $customItemXrefEntity;

    public function __construct(CustomItem $customItem, string $entityType, int $entityId)
    {
        $this->customItem = $customItem;
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
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

    /**
     * @return ?CustomItemXrefInterface
     */
    public function getXrefEntity(): ?CustomItemXrefInterface
    {
        return $this->customItemXrefEntity;
    }
}
