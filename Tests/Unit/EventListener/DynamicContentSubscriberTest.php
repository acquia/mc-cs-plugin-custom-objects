<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Statement;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DynamicContentSubscriberTest extends TestCase
{
    /** @var ConfigProvider|MockObject */
    private $configProviderMock;

    /** @var QueryFilterHelper|MockObject */
    private $queryFilterHelperMock;

    /** @var QueryFilterFactory|MockObject */
    private $queryFilterFactory;

    /** @var Logger|MockObject */
    private $loggerMock;

    /** @var QueryBuilder|MockObject */
    private $queryBuilderMock;

    /** @var DynamicContentSubscriber */
    private $dynamicContentSubscriber;

    /** @var Statement|MockObject */
    private $statementMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProviderMock    = $this->createMock(ConfigProvider::class);
        $this->queryFilterFactory    = $this->createMock(QueryFilterFactory::class);
        $this->queryFilterHelperMock = $this->createMock(QueryFilterHelper::class);
        $this->loggerMock            = $this->createMock(Logger::class);
        $this->queryBuilderMock      = $this->createMock(QueryBuilder::class);
        $this->statementMock         = $this->createMock(Statement::class);

        $this->dynamicContentSubscriber = new DynamicContentSubscriber(
            $this->queryFilterFactory,
            $this->queryFilterHelperMock,
            $this->configProviderMock,
            $this->loggerMock
        );
    }

    public function testOnCampaignBuildWhenPluginDisabled(): void
    {
        $this->configProviderMock->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->queryFilterFactory->expects($this->never())->method('configureQueryBuilderFromSegmentFilter');

        $this->dynamicContentSubscriber->evaluateFilters($this->buildEventWithFilters());
    }

    public function testFiltersNotEvaluatedIfEventMarkedEvaluated(): void
    {
        $this->configProviderMock->expects($this->once())->method('pluginIsEnabled')->willReturn(true);

        $event = $this->buildEventWithFilters();
        $event->setIsEvaluated(true);

        $this->queryFilterFactory->expects($this->never())->method('configureQueryBuilderFromSegmentFilter');

        $this->dynamicContentSubscriber->evaluateFilters($event);
    }

    public function testFiltersInsertedIntoEvent(): void
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->configProviderMock->expects($this->once())->method('pluginIsEnabled')->willReturn(true);

        $this->queryFilterFactory->expects($this->exactly(2))
            ->method('configureQueryBuilderFromSegmentFilter')
            ->withConsecutive(
                [
                    [
                        'type'          => CustomFieldFilterQueryBuilder::getServiceId(),
                        'table'         => 'custom_field_text',
                        'field'         => 'cfwq_1',
                        'foreign_table' => 'custom_objects',
                    ],
                    'filter_custom_field_1',
                ],
                [
                    [
                        'type'          => CustomItemNameFilterQueryBuilder::getServiceId(),
                        'table'         => 'custom_field_text',
                        'field'         => 'cowq_2',
                        'foreign_table' => 'custom_objects',
                    ],
                    'filter_custom_item_1',
                ]
            )
            ->will($this->onConsecutiveCalls(
                $this->queryBuilderMock,
                $this->throwException(new InvalidSegmentFilterException('Testing invalid segment handling here.'))
            ));

        $event = $this->buildEventWithFilters();
        $event->setIsEvaluated(false);

        $this->queryBuilderMock->expects($this->once())->method('execute')->willReturn($this->statementMock);

        $this->loggerMock
            ->expects($this->never())
            ->method('addError');

        $this->dynamicContentSubscriber->evaluateFilters($event);
    }

    private function buildEventWithFilters(): ContactFiltersEvaluateEvent
    {
        return new ContactFiltersEvaluateEvent(
            [
                'custom_field_1' => [
                    'type'          => CustomFieldFilterQueryBuilder::getServiceId(),
                    'table'         => 'custom_field_text',
                    'field'         => 'cfwq_1',
                    'foreign_table' => 'custom_objects',
                ],
                'custom_item_1'  => [
                    'type'          => CustomItemNameFilterQueryBuilder::getServiceId(),
                    'table'         => 'custom_field_text',
                    'field'         => 'cowq_2',
                    'foreign_table' => 'custom_objects',
                ],
            ],
            new Lead()
        );
    }
}
