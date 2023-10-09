<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\Exception\FixtureNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomItemNameFilterQueryBuilderTest extends MauticMysqlTestCase
{
    use FixtureObjectsTrait;
    use DbalQueryTrait;

    /**
     * @throws FixtureNotFoundException
     * @throws NotFoundException
     */
    public function testApplyQuery(): void
    {
        $fixturesDirectory   = $this->getFixturesDirectory();
        $objects             = $this->loadFixtureFiles([
            $fixturesDirectory.'/leads.yml',
            $fixturesDirectory.'/custom_objects.yml',
            $fixturesDirectory.'/custom_fields.yml',
            $fixturesDirectory.'/custom_items.yml',
            $fixturesDirectory.'/custom_xref.yml',
            $fixturesDirectory.'/custom_values.yml',
        ]);

        $this->setFixtureObjects($objects);
        /** @var CustomFieldTypeProvider $fieldTypeProvider */
        $fieldTypeProvider = $this->getContainer()->get('custom_field.type.provider');

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->getContainer()->get('custom_field.repository');

        $filterHelper = new QueryFilterHelper(
            $this->em,
            new QueryFilterFactory(
                $this->em,
                $fieldTypeProvider,
                $customFieldRepository,
                new QueryFilterFactory\Calculator(),
                1
            ),
            new RandomParameterName()
        );
        $queryBuilderService = new CustomItemNameFilterQueryBuilder(
            new RandomParameterName(),
            $filterHelper,
            $dispatcher
        );
        $filterMock   = $this->createSegmentFilterMock('%emotion%', 'text', 'like', 'custom_object3');
        $queryBuilder = $this->getLeadsQueryBuilder();

        $queryBuilderService->applyQuery($queryBuilder, $filterMock);

        $this->assertSame(2, $this->executeSelect($queryBuilder)->rowCount());
    }

    /**
     * @param mixed  $value
     * @param string $type
     * @param string $operator
     * @param string $fixtureField
     *
     * @throws FixtureNotFoundException
     */
    private function createSegmentFilterMock($value, $type = 'text', $operator = 'eq', $fixtureField = 'custom_field1'): ContactSegmentFilter
    {
        /** @var MockObject|ContactSegmentFilter $filterMock */
        $filterMock = $this->getMockBuilder(ContactSegmentFilter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filterMock->method('getType')->willReturn($type);
        $filterMock->method('getOperator')->willReturn($operator);
        $filterMock->method('getField')->willReturn((string) $this->getFixtureById($fixtureField)->getId());
        $filterMock->method('getParameterValue')->willReturn($value);
        $filterMock->method('getParameterHolder')->willReturn((string) ':needle');

        return $filterMock;
    }

    private function getLeadsQueryBuilder(): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('l.*')->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        return $queryBuilder;
    }
}
