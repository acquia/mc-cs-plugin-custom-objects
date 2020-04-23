<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Event;

use MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent;
use \PHPUnit\Framework\TestCase;

class CustomObjectListFormatEventTest extends TestCase
{
    public function testIsFormattedFlag(): void {
        $event = new CustomObjectListFormatEvent([], 'format');
        $this->assertFalse($event->hasBeenFormatted());
        $this->assertEquals('', $event->getFormattedString());

        $event->setFormattedString('a formatted string');
        $this->assertTrue($event->hasBeenFormatted());
    }
}