<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;

class CustomItemXrefEntityEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $xref  = $this->createMock(CustomItemXrefContact::class);
        $event = new CustomItemXrefEntityEvent($xref);

        $this->assertSame($xref, $event->getXref());
    }
}
