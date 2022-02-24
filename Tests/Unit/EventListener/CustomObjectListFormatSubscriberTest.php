<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomObjectListFormatSubscriber;
use MauticPlugin\CustomObjectsBundle\Helper\TokenFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomObjectListFormatSubscriberTest extends TestCase
{
    /**
     * @var TokenFormatter|MockObject
     */
    private $tokenFormatterMock;

    /**
     * @var CustomObjectListFormatSubscriber
     */
    private $subscriber;

    /**
     * @var CustomObjectListFormatEvent|MockObject
     */
    private $eventMock;

    public function setUp(): void
    {
        $this->tokenFormatterMock = $this->createMock(TokenFormatter::class);
        $this->subscriber         = new CustomObjectListFormatSubscriber($this->tokenFormatterMock);
        $this->eventMock          = $this->createMock(CustomObjectListFormatEvent::class);

        parent::setUp();
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [CustomObjectEvents::ON_CUSTOM_OBJECT_LIST_FORMAT => ['onFormatList', 0]],
            CustomObjectListFormatSubscriber::getSubscribedEvents()
        );
    }

    public function testOnFormatInvalid(): void
    {
        $format = 'format';

        $this->eventMock->expects($this->once())
            ->method('getFormat')
            ->willReturn($format);

        $this->tokenFormatterMock->expects($this->once())
            ->method('isValidFormat')
            ->with($format)
            ->willReturn(false);

        $this->eventMock->expects($this->never())
            ->method('getCustomObjectValues');
        $this->tokenFormatterMock->expects($this->never())
            ->method('format');
        $this->eventMock->expects($this->never())
            ->method('setFormattedString');

        $this->subscriber->onFormatList($this->eventMock);
    }

    public function testOnFormat(): void
    {
        $format          = 'format';
        $values          = ['values'];
        $formattedString = 'formattedString';

        $this->eventMock->expects($this->once())
            ->method('getFormat')
            ->willReturn($format);

        $this->tokenFormatterMock->expects($this->once())
            ->method('isValidFormat')
            ->with($format)
            ->willReturn(true);

        $this->eventMock->expects($this->once())
            ->method('getCustomObjectValues')
            ->willReturn($values);

        $this->tokenFormatterMock->expects($this->once())
            ->method('format')
            ->with($values, $format)
            ->willReturn($formattedString);

        $this->eventMock->expects($this->once())
            ->method('setFormattedString')
            ->with($formattedString);

        $this->subscriber->onFormatList($this->eventMock);
    }
}
