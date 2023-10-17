<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
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
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $randomParameter = new RandomParameterName();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->method('getConnection')
            ->willReturn($this->createMock(Connection::class));

        $queryFilterHelper = new QueryFilterHelper(
            $entityManager,
            new QueryFilterFactory(
                $entityManager,
                new CustomFieldTypeProvider(),
                $this->createMock(CustomFieldRepository::class),
                new QueryFilterFactory\Calculator(),
                1
            ),
            new RandomParameterName()
        );

        $this->queryBuilder         = $this->createMock(QueryBuilder::class);
        $this->contactSegmentFilter = $this->createMock(ContactSegmentFilter::class);
        $this->connection           = $this->createMock(Connection::class);
        $this->expressionBuilder    = $this->createMock(ExpressionBuilder::class);

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
     *
     * @param $parameterValue
     */
    public function testApplyQuery($parameterValue): void
    {
        $this->contactSegmentFilter
            ->method('getField')
            ->willReturnOnConsecutiveCalls('field1', '1');

        $this->contactSegmentFilter
            ->method('getOperator')
            ->willReturnOnConsecutiveCalls('eq', 'eq');

        $this->contactSegmentFilter
            ->expects($this->once())
            ->method('getParameterValue')
            ->willReturn($parameterValue);

        $this->queryBuilder
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->queryBuilder
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
