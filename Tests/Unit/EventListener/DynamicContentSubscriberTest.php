<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Helper\ContactFilterMatcher;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DynamicContentSubscriberTest extends TestCase
{
    /** @var QueryFilterFactory&MockObject */
    private MockObject $queryFilterFactory;

    /** @var ContactFilterMatcher&MockObject */
    private MockObject $contactFilterMatcher;

    /** @var ConfigProvider&MockObject */
    private MockObject $configProvider;

    private DynamicContentSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryFilterFactory   = $this->createMock(QueryFilterFactory::class);
        $this->contactFilterMatcher = $this->createMock(ContactFilterMatcher::class);
        $this->configProvider       = $this->createMock(ConfigProvider::class);

        $this->subscriber = new DynamicContentSubscriber(
            $this->queryFilterFactory,
            $this->contactFilterMatcher,
            $this->configProvider,
        );
    }

    public function testGetSubscribedEventsReturnsCorrectMapping(): void
    {
        $events = DynamicContentSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE, $events);
        $this->assertSame(['evaluateFilters', 0], $events[DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE]);
    }

    public function testEvaluateFiltersSkipsWhenEventAlreadyEvaluated(): void
    {
        $event = $this->buildEvent();
        $event->setIsEvaluated(true);

        // pluginIsEnabled must never be called — early exit on isEvaluated()
        $this->configProvider->expects($this->never())->method('pluginIsEnabled');
        $this->queryFilterFactory->expects($this->never())->method('configureQueryBuilderFromSegmentFilter');
        $this->contactFilterMatcher->expects($this->never())->method('match');

        $this->subscriber->evaluateFilters($event);
    }

    public function testEvaluateFiltersSkipsWhenPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->queryFilterFactory->expects($this->never())->method('configureQueryBuilderFromSegmentFilter');
        $this->contactFilterMatcher->expects($this->never())->method('match');

        $this->subscriber->evaluateFilters($this->buildEvent());
    }

    public function testEvaluateFiltersSkipsWhenNoCustomObjectFiltersFound(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        // All filters throw → no custom object filters present
        $this->queryFilterFactory->expects($this->exactly(2))
            ->method('configureQueryBuilderFromSegmentFilter')
            ->willThrowException(new InvalidSegmentFilterException('not a CO filter'));

        $this->contactFilterMatcher->expects($this->never())->method('match');

        $event = $this->buildEvent();
        $this->subscriber->evaluateFilters($event);

        $this->assertFalse($event->isEvaluated());
    }

    public function testEvaluateFiltersMatchesAndSetsResultOnEvent(): void
    {
        $contact = new Lead();
        $contact->setFields(['email' => 'test@example.com']);

        $event = new ContactFiltersEvaluateEvent($this->buildFilters(), $contact);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        // First filter throws (not a CO filter), second succeeds → hasCustomObjectFilters returns true
        $this->queryFilterFactory->expects($this->exactly(2))
            ->method('configureQueryBuilderFromSegmentFilter')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new InvalidSegmentFilterException('not a CO filter')),
                $this->returnValue(null),
            );

        $this->contactFilterMatcher->expects($this->once())
            ->method('match')
            ->with($this->buildFilters(), ['email' => 'test@example.com'])
            ->willReturn(true);

        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertTrue($event->isMatched());
    }

    public function testEvaluateFiltersSetsMismatchOnEvent(): void
    {
        $contact = new Lead();
        $contact->setFields([]);

        $event = new ContactFiltersEvaluateEvent($this->buildFilters(), $contact);

        $this->configProvider->method('pluginIsEnabled')->willReturn(true);

        // First filter is a valid CO filter → hasCustomObjectFilters returns true immediately
        $this->queryFilterFactory->expects($this->once())
            ->method('configureQueryBuilderFromSegmentFilter')
            ->willReturn(null);

        $this->contactFilterMatcher->expects($this->once())
            ->method('match')
            ->willReturn(false);

        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertFalse($event->isMatched());
    }

    /**
     * @return mixed[]
     */
    private function buildFilters(): array
    {
        return [
            'custom_field_1' => [
                'type'          => CustomFieldFilterQueryBuilder::getServiceId(),
                'table'         => 'custom_field_text',
                'field'         => 'cfwq_1',
                'foreign_table' => 'custom_objects',
            ],
            'custom_item_1' => [
                'type'          => CustomItemNameFilterQueryBuilder::getServiceId(),
                'table'         => 'custom_field_text',
                'field'         => 'cowq_2',
                'foreign_table' => 'custom_objects',
            ],
        ];
    }

    private function buildEvent(): ContactFiltersEvaluateEvent
    {
        return new ContactFiltersEvaluateEvent($this->buildFilters(), new Lead());
    }
}
