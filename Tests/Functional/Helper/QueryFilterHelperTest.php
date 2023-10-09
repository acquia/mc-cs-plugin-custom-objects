<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Helper;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;

class QueryFilterHelperTest extends MauticMysqlTestCase
{
    use FixtureObjectsTrait;

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

        $this->filterFactory = self::$container->get('mautic.lead.model.lead_segment_filter_factory');

        /** @var CustomFieldTypeProvider $fieldTypeProvider */
        $fieldTypeProvider = self::$container->get('custom_field.type.provider');
        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = self::$container->get('custom_field.repository');
        $this->filterHelper    = new QueryFilterHelper(
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

        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles([
            $fixturesDirectory.'/leads.yml',
            $fixturesDirectory.'/custom_objects.yml',
            $fixturesDirectory.'/custom_fields.yml',
            $fixturesDirectory.'/custom_items.yml',
            $fixturesDirectory.'/custom_xref.yml',
            $fixturesDirectory.'/custom_values.yml',
            $fixturesDirectory.'/custom-item-relation-filter-query-builder-fixture-2.yml',
        ]);
        $this->setFixtureObjects($objects);
    }

    public function testGetCustomValueValueExpression(): void
    {
        $this->assertMatchWhere(
            'test_value.value = :par0',
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'type'     => 'custom_object',
                'operator' => 'eq',
                'value'    => 'love',
            ]
        );

        $this->assertMatchWhere(
            'test_value.value LIKE :par1',
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'type'     => 'custom_object',
                'operator' => 'like',
                'value'    => 'love',
            ]
        );

        $this->assertMatchWhere(
            '(test_value.value <> :par2) OR (test_value.value IS NULL)',
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'type'     => 'custom_object',
                'operator' => 'neq',
                'value'    => 'love',
            ]
        );

        $this->assertMatchWhere(
            'test_value.value > :par3',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object'     => 'custom_object',
                'type'       => 'int',
                'operator'   => 'gt',
                'properties' => [
                    'filter' => '500',
                ],
            ]
        );
    }

    protected function assertMatchWhere(string $expectedWhere, array $filter): void
    {
        $unionQueryContainer = new UnionQueryContainer();
        $qb                  = new QueryBuilder($this->em->getConnection());
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
