<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Helper;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class QueryFilterHelperTest extends MauticWebTestCase
{
    use FixtureObjectsTrait;
    use DatabaseSchemaTrait;

    /**
     * @var ContactSegmentFilterFactory
     */
    private $filterFactory;

    /**
     * @var QueryFilterHelper
     */
    private $filterHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filterFactory = $this->getContainer()->get('mautic.lead.model.lead_segment_filter_factory');

        /** @var CustomFieldTypeProvider $fieldTypeProvider */
        $fieldTypeProvider  = $this->getContainer()->get('custom_field.type.provider');
        $this->filterHelper = new QueryFilterHelper(
            $this->em,
            $fieldTypeProvider,
            $this->getContainer()->get('mautic.helper.core_parameters')
        );

        $this->createFreshDatabaseSchema($this->em);
        $this->postFixtureSetup();
        $fixturesDirectory = $this->getFixturesDirectory();

        $objects = $this->loadFixtureFiles([
            $fixturesDirectory.'/roles.yml',
            $fixturesDirectory.'/users.yml',
            $fixturesDirectory.'/leads.yml',
            $fixturesDirectory.'/custom_objects.yml',
            $fixturesDirectory.'/custom_fields.yml',
            $fixturesDirectory.'/custom_items.yml',
            $fixturesDirectory.'/custom_xref.yml',
            $fixturesDirectory.'/custom_values.yml',
            $fixturesDirectory.'/custom-item-relation-filter-query-builder-fixture-2.yml',
        ], false, null, 'doctrine');

        $this->setFixtureObjects($objects);
    }

    public function testGetCustomValueValueExpression(): void
    {
        $this->assertMatchWhere(
            'test_value.value = :test_value_value',
            [
                'glue' => 'and',
                'field' => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'type' => 'custom_object',
                'operator' => 'eq',
                'value' => 'love',
            ]
        );

        $this->assertMatchWhere(
            'test_value.value LIKE :test_value_value',
            [
                'glue' => 'and',
                'field' => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'type' => 'custom_object',
                'operator' => 'like',
                'value' => 'love',
            ]
        );

        $this->assertMatchWhere(
            '(test_value.value <> :test_value_value) OR (test_value.value IS NULL)',
            [
                'glue' => 'and',
                'field' => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'type' => 'custom_object',
                'operator' => 'neq',
                'value' => 'love',
            ]
        );

        $this->assertMatchWhere(
            'test_value.value > :test_value_value',
            [
                'glue' => 'and',
                'field' => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object' => 'custom_object',
                'type' => 'int',
                'operator' => 'gt',
                'properties' => [
                    'filter' => '500',
                ],
            ]
        );
    }

    protected function assertMatchWhere(string $expectedWhere, array $filter): void
    {
        $unionQueryContainer = new UnionQueryContainer();
        $qb = new QueryBuilder($this->em->getConnection());
        $unionQueryContainer->add($qb);

        $this->filterHelper->addCustomFieldValueExpressionFromSegmentFilter(
            $unionQueryContainer,
            'test',
            $this->filterFactory->factorSegmentFilter($filter)
        );

        $unionQueryContainer->rewind();
        $whereResponse = (string) $unionQueryContainer->current()->getQueryPart('where');
        $this->assertSame($expectedWhere, $whereResponse);
    }
}
