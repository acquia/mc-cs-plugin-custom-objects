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
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomObjectEvent extends Event
{
    /**
     * @var CustomObject
     */
    private $customObject;

    /**
     * @var bool
     */
    private $isNew;

    /**
     * @param CustomObject $customObject
     */
    public function __construct(CustomObject $customObject, bool $isNew = false)
    {
        $this->customObject = $customObject;
        $this->isNew        = $isNew;
    }

    /**
     * @return CustomObject
     */
    public function getCustomObject(): CustomObject
    {
        return $this->customObject;
    }

    /**
     * @return bool
     */
    public function entityIsNew(): bool
    {
        return $this->isNew;
    }
}
