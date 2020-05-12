<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Event;

use MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent;
use PHPUnit\Framework\TestCase;

class CustomObjectListFormatEventTest extends TestCase
{
    public function testAll(): void
    {
        // Test default construct
        $event = new CustomObjectListFormatEvent([]);
        $this->assertSame([], $event->getCustomObjectValues());
        $this->assertSame('default', $event->getFormat());
        $this->assertSame('', $event->getFormattedString());
        $this->assertFalse($event->hasBeenFormatted());

        // Test format setter
        $event->setFormattedString('');
        $this->assertSame('', $event->getFormattedString());
        $this->assertFalse($event->hasBeenFormatted());

        $event->setFormattedString('a formatted string');
        $this->assertSame('a formatted string', $event->getFormattedString());
        $this->assertTrue($event->hasBeenFormatted());

        $event->setFormattedString('');
        $this->assertSame('a formatted string', $event->getFormattedString());
        $this->assertTrue($event->hasBeenFormatted());

        // Test custom construct
        $event = new CustomObjectListFormatEvent(['someValues'], 'someFormat');
        $this->assertSame(['someValues'], $event->getCustomObjectValues());
        $this->assertSame('someFormat', $event->getFormat());
        $this->assertSame('', $event->getFormattedString());
        $this->assertFalse($event->hasBeenFormatted());
    }
}
