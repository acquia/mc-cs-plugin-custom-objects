<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Event;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;

class CustomItemEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $customItem = $this->createMock(CustomItem::class);
        $event      = new CustomItemEvent($customItem);

        $this->assertSame($customItem, $event->getCustomItem());
        $this->assertFalse($event->entityIsNew());
    }
}
