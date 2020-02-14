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

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use Symfony\Component\EventDispatcher\Event;

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
