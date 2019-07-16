<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Helper;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;

class QueryFilterHelperTest extends WebTestCase
{
    use FixtureObjectsTrait;
    use DatabaseSchemaTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    private $filterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManager $entityManager */
        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;
        $this->filterFactory = $this->getContainer()->get('mautic.lead.model.lead_segment_filter_factory');
        $fixturesDirectory   = $this->getFixturesDirectory();

        $this->createFreshDatabaseSchema($entityManager);
        $this->postFixtureSetup();

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
    }

    public function testGetCustomValueValueExpression(): void
    {
        /** @var CustomFieldTypeProvider $fieldTypeProvider */
        $fieldTypeProvider = $this->getContainer()->get('custom_field.type.provider');
        $filterHelper      = new QueryFilterHelper($fieldTypeProvider);

        $filters = [
            [
                'filter' => ['glue' => 'and', 'field' => 'cmf_'.$this->getFixtureById('custom_field1')->getId(), 'type' => 'custom_object', 'operator' => 'eq', 'value' => 'love'],
                'match'  => 'test_value.value = :test_value_value',
            ],
            [
                'filter' => ['glue' => 'and', 'field' => 'cmf_'.$this->getFixtureById('custom_field1')->getId(), 'type' => 'custom_object', 'operator' => 'like', 'value' => 'love'],
                'match'  => 'test_value.value LIKE :test_value_value',
            ],
            [
                'filter' => ['glue' => 'and', 'field' => 'cmf_'.$this->getFixtureById('custom_field1')->getId(), 'type' => 'custom_object', 'operator' => 'neq', 'value' => 'love'],
                'match'  => '(test_value.value <> :test_value_value) OR (test_value.value IS NULL)',
            ],
        ];

        foreach ($filters as $filter) {
            $queryBuilder = new QueryBuilder($this->entityManager->getConnection());

            $filterHelper->addCustomFieldValueExpressionFromSegmentFilter(
                $queryBuilder, 'test', $this->filterFactory->factorSegmentFilter($filter['filter'])
            );

            $whereResponse = (string) $queryBuilder->getQueryPart('where');
            $this->assertSame($filter['match'], $whereResponse);
        }
    }
}
