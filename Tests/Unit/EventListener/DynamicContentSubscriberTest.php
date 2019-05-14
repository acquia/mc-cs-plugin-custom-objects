<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManager;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemFilterQueryBuilder;
use Monolog\Logger;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls;
use PHPUnit_Framework_TestCase;
use UnexpectedValueException;

class DynamicContentSubscriberTest extends PHPUnit_Framework_TestCase
{
    /** @var ConfigProvider|PHPUnit_Framework_MockObject_MockObject */
    private $configProviderMock;

    /** @var ContactSegmentFilterFactory|PHPUnit_Framework_MockObject_MockObject */
    private $segmentFilterFactoryMock;

    /** @var QueryFilterHelper|PHPUnit_Framework_MockObject_MockObject */
    private $queryFilterHelperMock;

    /** @var EntityManager|PHPUnit_Framework_MockObject_MockObject */
    private $entityManagerMock;

    /** @var ContactFiltersEvaluateEvent|PHPUnit_Framework_MockObject_MockObject */
    private $evaluateEvent;

    /** @var Lead|PHPUnit_Framework_MockObject_MockObject */
    private $leadMock;

    /** @var Logger|PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /** @var QueryBuilder|PHPUnit_Framework_MockObject_MockObject */
    private $queryBuilderMock;

    /** @var DynamicContentSubscriber */
    private $dynamicContentSubscriber;

    /** @var Statement|PHPUnit_Framework_MockObject_MockObject */
    private $statementMock;

    public function setUp()
    {
        parent::setUp();

        $this->configProviderMock       = $this->createMock(ConfigProvider::class);
        $this->entityManagerMock        = $this->createMock(EntityManager::class);
        $this->segmentFilterFactoryMock = $this->createMock(ContactSegmentFilterFactory::class);

        $this->queryFilterHelperMock = $this->createMock(QueryFilterHelper::class);
        $this->evaluateEvent         = $this->createMock(ContactFiltersEvaluateEvent::class);

        $this->leadMock   = $this->createMock(Lead::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->statementMock    = $this->createMock(Statement::class);

        $this->dynamicContentSubscriber = new DynamicContentSubscriber(
            $this->entityManagerMock,
            $this->segmentFilterFactoryMock,
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

        $this->entityManagerMock->expects($this->never())->method('getConnection');

        $this->configProviderMock->expects($this->once())->method('pluginIsEnabled')->willReturn(true);

        $this->evaluateEvent->expects($this->once())->method('getFilters')->willReturn([]);
        $this->evaluateEvent->expects($this->once())->method('isEvaluated')->willReturn(true);


        $this->dynamicContentSubscriber->evaluateFilters($this->evaluateEvent);
    }

    public function testFiltersInsertedIntoEvent(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', 'test');

        $connectionMock = $this->createMock(Connection::class);
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

        $types = array_column($filterObject, 'type');

        $segmentFilter = $this->createMock(ContactSegmentFilter::class);
        $segmentFilter->expects($this->exactly(count($filterObject)))
            ->method('getTable')
            ->willReturn(MAUTIC_TABLE_PREFIX.'custom_objects');

        $segmentFilter->expects($this->any())
            ->method('getQueryType')
            ->will(new PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($types));

        $this->leadMock->expects($this->exactly(2))->method('getId')->willReturn(1);

        $this->entityManagerMock->expects($this->once())->method('getConnection')->willReturn($connectionMock);
        $this->configProviderMock->expects($this->once())->method('pluginIsEnabled')->willReturn(true);

        $this->evaluateEvent->expects($this->once())->method('getFilters')->willReturn($filterObject);
        $this->evaluateEvent->expects($this->once())->method('isEvaluated')->willReturn(false);
        $this->evaluateEvent->expects($this->exactly(2))->method('getContact')->willReturn($this->leadMock);

        $this->queryBuilderMock->expects($this->exactly(2))->method('execute')->willReturn($this->statementMock);

        $this->segmentFilterFactoryMock->expects($this->exactly(count($filterObject)))
            ->method('factorSegmentFilter')
            ->willReturn($segmentFilter);

        $segmentFilter->method('getOperator')->willReturn('eq');
        $segmentFilter->method('getParameterValue')->willReturn('not-a-value');

        $this->queryFilterHelperMock
            ->expects($this->once())
            ->method('addCustomObjectNameExpression');

        $this->queryFilterHelperMock
            ->expects($this->once())
            ->method('createValueQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $this->queryFilterHelperMock
            ->expects($this->once())
            ->method('createItemNameQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $this->loggerMock
            ->expects($this->never())
            ->method('addError');

        try {
            $this->dynamicContentSubscriber->evaluateFilters($this->evaluateEvent);
        }
        catch (UnexpectedValueException $e) {
        }
    }
}


