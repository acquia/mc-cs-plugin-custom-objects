<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomObjectListFormatSubscriber;
use \PHPUnit\Framework\TestCase;

class CustomObjectListFormatSubscriberTest extends TestCase
{
    /**
     * @var CustomObjectListFormatSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new CustomObjectListFormatSubscriber();
    }

    public function testInvalidFormat(): void {
        $event = new CustomObjectListFormatEvent(['value1'], 'A BAD FORMAT');
        $this->subscriber->formatCustomObjectsList($event);

        $this->assertFalse($event->hasBeenFormatted());
        $this->assertEquals('', $event->getFormattedString());
    }

    public function testNoValues(): void {
        $event = new CustomObjectListFormatEvent([], 'default');
        $this->subscriber->formatCustomObjectsList($event);

        $this->assertFalse($event->hasBeenFormatted());
    }

    public function testFormatValues(): void {
        $values = ['value1', 'value2', 'value3'];

        // Test default formatter
        $event1 = new CustomObjectListFormatEvent($values, 'default');
        $this->subscriber->formatCustomObjectsList($event1);
        $expected = "value1, value2, value3";
        $this->assertTrue($event1->hasBeenFormatted());
        $this->assertEquals($expected, $event1->getFormattedString());

        // Test and formatter
        $event2 = new CustomObjectListFormatEvent($values, 'and-list');
        $this->subscriber->formatCustomObjectsList($event2);
        $expected = "value1, value2 and value3";
        $this->assertTrue($event2->hasBeenFormatted());
        $this->assertEquals($expected, $event2->getFormattedString());
    }

}