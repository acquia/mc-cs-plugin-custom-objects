<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class CustomFieldFilterQueryBuilderTest extends WebTestCase
{
    use FixtureObjectsTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $em         = $this->getContainer()->get('doctrine')->getManager();
        $metadata   = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadata)) {
            $schemaTool->createSchema($metadata);
        }
        $this->postFixtureSetup();

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
        $queryHelper       = new QueryFilterHelper($fieldTypeProvider);

        $queryBuilderService = new CustomFieldFilterQueryBuilder(new RandomParameterName(), $queryHelper);

        $filterMock = $this->createSegmentFilterMock('hate');

        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder, $filterMock);

        $this->assertSame(2, $queryBuilder->execute()->rowCount());

        $filterMock = $this->createSegmentFilterMock('love');

        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder, $filterMock);

        $this->assertSame(3, $queryBuilder->execute()->rowCount());
    }

    /**
     * @param mixed  $value
     * @param string $type
     * @param string $operator
     * @param string $fixtureField
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     *
     * @throws \MauticPlugin\CustomObjectsBundle\Tests\Functional\Exception\FixtureNotFoundException
     */
    private function createSegmentFilterMock(
        $value,
        string $type = 'text',
        string $operator = 'eq',
        string $fixtureField = 'custom_field1'
    ): \PHPUnit_Framework_MockObject_MockObject {
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
