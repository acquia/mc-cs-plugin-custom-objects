<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemFilterQueryBuilder;
use Monolog\Logger;
use PHPUnit_Framework_TestCase;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidSegmentFilterException;

class DynamicContentSubscriberTest extends PHPUnit_Framework_TestCase
{
    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $configProviderMock;

    /** @var QueryFilterHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $queryFilterHelperMock;

    /** @var QueryFilterFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $queryFilterFactory;

    /** @var ContactFiltersEvaluateEvent|\PHPUnit_Framework_MockObject_MockObject */
    private $evaluateEvent;

    /** @var Lead|\PHPUnit_Framework_MockObject_MockObject */
    private $leadMock;

    /** @var Logger|\PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $queryBuilderMock;

    /** @var DynamicContentSubscriber */
    private $dynamicContentSubscriber;

    /** @var Statement|\PHPUnit_Framework_MockObject_MockObject */
    private $statementMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProviderMock    = $this->createMock(ConfigProvider::class);
        $this->queryFilterFactory    = $this->createMock(QueryFilterFactory::class);
        $this->queryFilterHelperMock = $this->createMock(QueryFilterHelper::class);
        $this->evaluateEvent         = $this->createMock(ContactFiltersEvaluateEvent::class);
        $this->leadMock              = $this->createMock(Lead::class);
        $this->loggerMock            = $this->createMock(Logger::class);
        $this->queryBuilderMock      = $this->createMock(QueryBuilder::class);
        $this->statementMock         = $this->createMock(Statement::class);

        $this->dynamicContentSubscriber = new DynamicContentSubscriber(
            $this->queryFilterFactory,
            $this->queryFilterHelperMock,
            $this->configProviderMock
        );

        $this->dynamicContentSubscriber->setLogger($this->loggerMock);
    }

    public function testOnCampaignBuildWhenPluginDisabled(): void
    {
        $this->configProviderMock->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->evaluateEvent->expects($this->never())->method('getFilters');

        $this->dynamicContentSubscriber->evaluateFilters($this->evaluateEvent);
    }

    public function testFiltersNotEvaluatedIfEventMarkedEvaluated(): void
    {
        $this->configProviderMock->expects($this->once())->method('pluginIsEnabled')->willReturn(true);

        $this->evaluateEvent->expects($this->once())->method('getFilters')->willReturn([]);
        $this->evaluateEvent->expects($this->once())->method('isEvaluated')->willReturn(true);
        $this->queryFilterFactory->expects($this->never())->method('configureQueryBuilderFromSegmentFilter');

        $this->dynamicContentSubscriber->evaluateFilters($this->evaluateEvent);
    }

    public function testFiltersInsertedIntoEvent(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $filterObject   = [
            'custom_field_1' => [
                'type'          => CustomFieldFilterQueryBuilder::getServiceId(),
                'table'         => 'custom_field_text',
                'field'         => 'cfwq_1',
                'foreign_table' => 'custom_objects',
            ],
            'custom_item_1'  => [
                'type'          => CustomItemFilterQueryBuilder::getServiceId(),
                'table'         => 'custom_field_text',
                'field'         => 'cowq_2',
                'foreign_table' => 'custom_objects',
            ],
        ];

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
                        'type'          => CustomItemFilterQueryBuilder::getServiceId(),
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

        $this->evaluateEvent->expects($this->once())->method('getFilters')->willReturn($filterObject);
        $this->evaluateEvent->expects($this->once())->method('isEvaluated')->willReturn(false);
        $this->evaluateEvent->expects($this->once())->method('getContact')->willReturn($this->leadMock);

        $this->queryBuilderMock->expects($this->once())->method('execute')->willReturn($this->statementMock);

        $this->loggerMock
            ->expects($this->never())
            ->method('addError');

        $this->dynamicContentSubscriber->evaluateFilters($this->evaluateEvent);
    }
}
