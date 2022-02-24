<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;

class CustomItemXrefEntityEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $xref  = $this->createMock(CustomItemXrefContact::class);
        $event = new CustomItemXrefEntityEvent($xref);

        $this->assertSame($xref, $event->getXref());
    }
}
