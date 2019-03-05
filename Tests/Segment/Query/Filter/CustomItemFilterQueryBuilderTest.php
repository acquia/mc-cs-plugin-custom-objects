<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Segment\Query\Filter;

use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Tests\DataFixtures\Traits\FixtureObjectsTrait;

class CustomItemFilterQueryBuilderTest extends WebTestCase
{
    use FixtureObjectsTrait;

    /** @var EntityManager */
    private $entityManager;

    protected function setUp(): void
    {
        $pluginDirectory   = $this->getContainer()->get('kernel')->locateResource('@CustomObjectsBundle');
        $fixturesDirectory = $pluginDirectory.'/Tests/DataFixtures/ORM/Data';

        $objects = $this->loadFixtureFiles([
            $fixturesDirectory.'/roles.yml',
            $fixturesDirectory.'/users.yml',
            $fixturesDirectory.'/leads.yml',
            $fixturesDirectory.'/custom_objects.yml',
            $fixturesDirectory.'/custom_fields.yml',
            $fixturesDirectory.'/custom_items.yml',
            $fixturesDirectory.'/custom_xref.yml',
            $fixturesDirectory.'/custom_values.yml',
        ], false, null, 'doctrine'); //,ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach ($this->getFixturesInUnloadableOrder() as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();

        parent::tearDown();
    }

    public function testApplyQuery(): void
    {
        $dispatcher        = $this->getContainer()->get('event_dispatcher');
        $fieldTypeProvider = new CustomFieldTypeProvider($dispatcher);
        $filterHelper      = new QueryFilterHelper($fieldTypeProvider);

        $queryBuilderService = new CustomItemFilterQueryBuilder(new RandomParameterName(), $filterHelper);

        $filterMock = $this->createSegmentFilterMock('%emotion%', 'text', 'like');

        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder, $filterMock);

        $this->assertSame(1, $queryBuilder->execute()->rowCount());

        $filterMock = $this->createSegmentFilterMock('%Object%', 'text', 'like');

        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder, $filterMock);

        $this->assertSame(6, $queryBuilder->execute()->rowCount());
    }

    /**
     * @param        $value
     * @param string $type
     * @param string $operator
     * @param string $fixtureField
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     *
     * @throws \MauticPlugin\CustomObjectsBundle\Tests\Exception\FixtureNotFoundException
     */
    private function createSegmentFilterMock($value, $type = 'text', $operator = 'eq', $fixtureField = 'custom_field1'): \PHPUnit_Framework_MockObject_MockObject
    {
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
        $connection   = $this->entityManager->getConnection();
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder->select('l.*')->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        return $queryBuilder;
    }
}
