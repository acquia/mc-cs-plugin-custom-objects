<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Tests\DataFixtures\Traits\FixtureObjectsTrait;
use PHPUnit\Framework\MockObject\MockObject;

class QueryFilterHelperTest extends WebTestCase
{
    use FixtureObjectsTrait;

    /** @var EntityManager */
    private $entityManager;

    /** @var ContactSegmentFilterFactory */
    private $filterFactory;

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

        $pluginDirectory   = $this->getContainer()->get('kernel')->locateResource('@CustomObjectsBundle');
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
        ], false, null, 'doctrine'); //,ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);

        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->filterFactory = $this->getContainer()->get('mautic.lead.model.lead_segment_filter_factory');
    }

    public function testGetCustomValueValueExpression()
    {
        $fieldTypeProvider = $this->getContainer()->get('custom_field.type.provider');
        $filterHelper = new QueryFilterHelper($fieldTypeProvider);

        $queryBuilder = new QueryBuilder($this->entityManager->getConnection());

        $filters = [
            [
                'filter' => ['glue' => 'and', 'field' => 'cmf_' . $this->getFixtureById('custom_field1')->getId(), 'type' => 'custom_object', 'operator' => 'eq', 'value' => 'love'],
                'match'  => 'test_value.value = :test_value_value',
            ],
            [
                'filter' => ['glue' => 'and', 'field' => 'cmf_' . $this->getFixtureById('custom_field1')->getId(), 'type' => 'custom_object', 'operator' => 'like', 'value' => 'love'],
                'match'  => 'test_value.value LIKE :test_value_value',
            ],
            [
                'filter' => ['glue' => 'and', 'field' => 'cmf_' . $this->getFixtureById('custom_field1')->getId(), 'type' => 'custom_object', 'operator' => 'ne', 'value' => 'love'],
                'match'  => 'test_value.value != :test_value_value',
            ],
        ];

        foreach ($filters as $filter) {
            $filterHelper->addCustomFieldValueExpressionFromSegmentFilter(
                $queryBuilder, 'test', $this->filterFactory->factorSegmentFilter($filter['filter'])
            );

            $whereResponse = $queryBuilder->getQueryPart('where');
            $this->assertEquals($filter['match'], $whereResponse);
        }

        var_dump($queryBuilder->getSQL());

        //$traitContainer
    }

    private function setUpFixtures()
    {

    }

}
