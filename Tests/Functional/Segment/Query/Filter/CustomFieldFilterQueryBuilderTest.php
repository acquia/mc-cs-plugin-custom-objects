<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\Exception\FixtureNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomFieldFilterQueryBuilderTest extends MauticMysqlTestCase
{
    use FixtureObjectsTrait;
    use DbalQueryTrait;

    public function testApplyQuery(): void
    {
        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles([
            $fixturesDirectory.'/leads.yml',
            $fixturesDirectory.'/custom_objects.yml',
            $fixturesDirectory.'/custom_fields.yml',
            $fixturesDirectory.'/custom_items.yml',
            $fixturesDirectory.'/custom_xref.yml',
            $fixturesDirectory.'/custom_values.yml',
        ], true);
        $this->setFixtureObjects($objects);

        /** @var CustomFieldTypeProvider $fieldTypeProvider */
        $fieldTypeProvider = $this->container->get('custom_field.type.provider');

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get('event_dispatcher');

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');

        $queryHelper = new QueryFilterHelper(
            $this->em,
            new QueryFilterFactory(
                $this->em,
                $fieldTypeProvider,
                $customFieldRepository,
                new QueryFilterFactory\Calculator(),
                1
            )
        );
        $queryBuilderService = new CustomFieldFilterQueryBuilder(
            new RandomParameterName(),
            $dispatcher,
            $queryHelper
        );

        /** @var ContactSegmentFilter $filterMock */
        $filterMock   = $this->createSegmentFilterMock('hate');
        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder, $filterMock);

        $this->assertSame(2, $this->executeSelect($queryBuilder)->rowCount());

        /** @var ContactSegmentFilter $filterMock */
        $filterMock   = $this->createSegmentFilterMock('love');
        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder, $filterMock);

        $this->assertSame(3, $this->executeSelect($queryBuilder)->rowCount());
    }

    /**
     * @param mixed $value
     *
     * @throws FixtureNotFoundException
     */
    private function createSegmentFilterMock(
        $value,
        string $type = 'text',
        string $operator = 'eq',
        string $fixtureField = 'custom_field1'
    ): MockObject {
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
