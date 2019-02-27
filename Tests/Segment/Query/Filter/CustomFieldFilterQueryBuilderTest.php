<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Segment\Query\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Tests\DataFixtures\Traits\FixtureObjectsTrait;

class CustomFieldFilterQueryBuilderTest extends WebTestCase
{
    use FixtureObjectsTrait;

    /** @var EntityManager */
    private $entityManager;

    public function setUp()
    {
        parent::setUp();

        $em = $this->getContainer()->get('doctrine')->getManager();
        if (!isset($metadatas)) {
            $metadatas = $em->getMetadataFactory()->getAllMetadata();
        }
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        if (!empty($metadatas)) {
            $schemaTool->createSchema($metadatas);
        }
        $this->postFixtureSetup();

        $pluginDirectory = $this->getContainer()->get('kernel')->locateResource('@CustomObjectsBundle');
        $fixturesDirectory = $pluginDirectory . '/Tests/DataFixtures/ORM/Data';

        $objects = $this->loadFixtureFiles([
            $fixturesDirectory . '/roles.yml',
            $fixturesDirectory . '/users.yml',
            $fixturesDirectory . '/leads.yml',
            $fixturesDirectory . '/custom_objects.yml',
            $fixturesDirectory . '/custom_fields.yml',
            $fixturesDirectory . '/custom_items.yml',
            $fixturesDirectory . '/custom_xref.yml',
            $fixturesDirectory . '/custom_values.yml',
        ], false, null,'doctrine'); //,ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

    }

    public function tearDown()
    {
        foreach ($this->getFixturesInUnloadableOrder() as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();
        return parent::tearDown();
    }

    public function testApplyQuery() {
        $queryBuilderService = new CustomFieldFilterQueryBuilder(new RandomParameterName());

        $filterMock = $this->createSegmentFilterMock('hate');

        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder,$filterMock);

        $this->assertEquals(2, $queryBuilder->execute()->rowCount());

        $filterMock = $this->createSegmentFilterMock('love');

        $queryBuilder = $this->getLeadsQueryBuilder();
        $queryBuilderService->applyQuery($queryBuilder,$filterMock);

        $this->assertEquals(3, $queryBuilder->execute()->rowCount());
    }

    private function createSegmentFilterMock($value) {
        $filterMock = $this->getMockBuilder(ContactSegmentFilter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filterMock->method('getType')->willReturn('text');
        $filterMock->method('getOperator')->willReturn('eq');
        $filterMock->method('getField')->willReturn((string) $this->getFixtureById('custom_field1')->getId());
        $filterMock->method('getParameterValue')->willReturn($value);
        $filterMock->method('getParameterHolder')->willReturn((string) ':needle');

        return $filterMock;
    }

    private function getLeadsQueryBuilder()
    {
        $connection   = $this->entityManager->getConnection();
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder->select('l.*')->from(MAUTIC_TABLE_PREFIX . "leads","l");

        return $queryBuilder;
    }
}
