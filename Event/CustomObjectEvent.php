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

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\EventDispatcher\Event;

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

    public function __construct(CustomObject $customObject, bool $isNew = false)
    {
        $this->customObject = $customObject;
        $this->isNew        = $isNew;
    }

    public function getCustomObject(): CustomObject
    {
        return $this->customObject;
    }

    public function entityIsNew(): bool
    {
        return $this->isNew;
    }
}
