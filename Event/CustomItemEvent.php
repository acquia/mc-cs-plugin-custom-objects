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

class CustomItemEvent extends Event
{
    /**
     * @var CustomItem
     */
    private $customItem = [];

    /**
     * @var bool
     */
    private $isNew;

    /**
     * @param CustomItem $customItem
     */
    public function __construct(CustomItem $customItem, bool $isNew = false)
    {
        $this->customItem = $customItem;
        $this->isNew      = $isNew;
    }

    /**
     * @return CustomItem
     */
    public function getCustomItem(): CustomItem
    {
        return $this->customItem;
    }

    /**
     * @return bool
     */
    public function entityIsNew(): bool
    {
        return $this->isNew;
    }
}
