<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\CustomFieldQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomItemNameFilterQueryBuilderTest extends TestCase
{
    /**
     * @var FilterQueryBuilderInterface
     */
    private $customItemNameFilterQueryBuilder;

    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilder;

    /**
     * @var ContactSegmentFilter|MockObject
     */
    private $contactSegmentFilter;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var ExpressionBuilder|MockObject
     */
    private $expressionBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $randomParameter = new RandomParameterName();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->method('getConnection')
            ->willReturn($this->createMock(Connection::class));
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $queryFilterHelper = new QueryFilterHelper(
            $entityManager,
            new CustomFieldQueryBuilder($entityManager, new CustomFieldTypeProvider(), $coreParametersHelper)
        );

        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->contactSegmentFilter = $this->createMock(ContactSegmentFilter::class);
        $this->connection = $this->createMock(Connection::class);
        $this->expressionBuilder = $this->createMock(ExpressionBuilder::class);

        $this->customItemNameFilterQueryBuilder = new CustomItemNameFilterQueryBuilder(
            $randomParameter,
            $queryFilterHelper,
            $eventDispatcher
        );
    }

    public function testGetServiceId(): void
    {
        $this->assertSame('mautic.lead.query.builder.custom_item.value', CustomItemNameFilterQueryBuilder::getServiceId());
    }

    /**
     * @dataProvider parameterValueProvider
     * @param $parameterValue
     */
    public function testApplyQuery($parameterValue): void
    {
        $this->contactSegmentFilter
            ->expects($this->at(0))
            ->method("getField")
            ->willReturn("field1");

        $this->contactSegmentFilter
            ->expects($this->at(1))
            ->method("getField")
            ->willReturn("1");

        $this->contactSegmentFilter
            ->expects($this->at(2))
            ->method("getOperator")
            ->willReturn("eq");

        $this->contactSegmentFilter
            ->expects($this->at(3))
            ->method("getParameterValue")
            ->willReturn($parameterValue);

        $this->contactSegmentFilter
            ->expects($this->at(4))
            ->method("getOperator")
            ->willReturn("eq");

        $this->queryBuilder
            ->expects($this->any())
            ->method("getConnection")
            ->willReturn($this->connection);

        $this->queryBuilder
            ->expects($this->any())
            ->method('expr')
            ->willReturn($this->expressionBuilder);

        $result = $this->customItemNameFilterQueryBuilder->applyQuery($this->queryBuilder, $this->contactSegmentFilter);
        $this->assertEquals($this->queryBuilder, $result);
    }

    public function parameterValueProvider()
    {
        return [
            ['mautic'],
            [10],
        ];
    }
}
