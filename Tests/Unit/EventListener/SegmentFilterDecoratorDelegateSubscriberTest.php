<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\LeadBundle\Event\LeadListFiltersDecoratorDelegateEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use MauticPlugin\CustomObjectsBundle\EventListener\SegmentFilterDecoratorDelegateSubscriber;
use MauticPlugin\CustomObjectsBundle\Segment\Decorator\MultiselectDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SegmentFilterDecoratorDelegateSubscriberTest extends TestCase
{
    /**
     * @var MultiselectDecorator|MockObject
     */
    private $multiselectDecorator;

    /**
     * @var SegmentFilterDecoratorDelegateSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->multiselectDecorator = $this->createMock(MultiselectDecorator::class);
        $this->subscriber           = new SegmentFilterDecoratorDelegateSubscriber(
            $this->multiselectDecorator
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [LeadEvents::SEGMENT_ON_DECORATOR_DELEGATE => 'onDecoratorDelegate'],
            SegmentFilterDecoratorDelegateSubscriber::getSubscribedEvents()
        );
    }

    public function testOnDecoratorDelegateNotCustomObject(): void
    {
        $crate = new ContactSegmentFilterCrate(['object' => 'someObject']);
        $event = new LeadListFiltersDecoratorDelegateEvent($crate);

        $this->subscriber->onDecoratorDelegate($event);

        $this->assertNull($event->getDecorator());
    }

    public function testOnDecoratorDelegateForCustomObject(): void
    {
        $crate = new ContactSegmentFilterCrate(['object' => 'someObject']);
        $event = new LeadListFiltersDecoratorDelegateEvent($crate);

        $this->subscriber->onDecoratorDelegate($event);

        $this->assertNull($event->getDecorator());
    }

    public function testOnDecoratorDelegateForCustomObjectWithMultiselect(): void
    {
        $crate = new ContactSegmentFilterCrate([
            'object' => 'custom_object',
            'type'   => 'multiselect',
        ]);
        $event = new LeadListFiltersDecoratorDelegateEvent($crate);

        $this->subscriber->onDecoratorDelegate($event);

        $this->assertSame($this->multiselectDecorator, $event->getDecorator());
    }
}
