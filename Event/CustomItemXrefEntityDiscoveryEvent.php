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

use Symfony\Component\EventDispatcher\Event;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;

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

    /**
     * @param integer $itemId
     * @param string  $entityType
     * @param integer $entityId
     */
    public function __construct(CustomItem $customItem, string $entityType, int $entityId)
    {
        $this->customItem = $customItem;
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
    }

    /**
     * @return CustomItem
     */
    public function getCustomItem(): CustomItem
    {
        return $this->customItem;
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @param CustomItemXrefInterface $customItemXrefEntity
     */
    public function setXrefEntity(CustomItemXrefInterface $customItemXrefEntity): void
    {
        $this->customItemXrefEntity = $customItemXrefEntity;
    }

    /**
     * @return CustomItemXrefInterface
     */
    public function getXrefEntity(): CustomItemXrefInterface
    {
        return $this->customItemXrefEntity;
    }
}
