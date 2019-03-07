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
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;

class CustomItemXrefContactEvent extends Event
{
    /**
     * @var CustomItemXrefContact
     */
    private $xRef;

    /**
     * @param CustomItemXrefContact $xRef
     */
    public function __construct(CustomItemXrefContact $xRef)
    {
        $this->xRef = $xRef;
    }

    /**
     * @return CustomItemXrefContact
     */
    public function getXref(): CustomItemXrefContact
    {
        return $this->xRef;
    }
}
