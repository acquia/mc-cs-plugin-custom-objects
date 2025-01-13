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
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'type'       => 'custom_object',
                'operator'   => 'like',
                'value'      => 'love',
                'filter'     => '500',
                'properties' => [
                    'filter' => '500',
                ],
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
                'filter'     => '500',
                'properties' => [
                    'filter' => '500',
                ],
            ]
        );

        $this->assertMatchWhere(
            "test_value.value BETWEEN '2024-05-15 00:00:00' AND '2024-05-24 23:59:59'",
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'between',
                'properties' => [
                    'filter' => [
                        'date_from' => 'May 15, 2024',
                        'date_to'   => 'May 24, 2024',
                    ],
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value LIKE :par5',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'like',
                'properties' => [
                    'filter' => '2024',
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value REGEXP :par6',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'regexp',
                'properties' => [
                    'filter' => '2024',
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value LIKE :par7',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'startsWith',
                'properties' => [
                    'filter' => '2024',
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value LIKE :par8',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'endsWith',
                'properties' => [
                    'filter' => '2024',
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value LIKE :par9',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_field1')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'contains',
                'properties' => [
                    'filter' => '2024',
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value IS NULL',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'empty',
                'properties' => [
                    'filter' => [],
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value IS NOT NULL',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => '!empty',
                'properties' => [
                    'filter' => [],
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value BETWEEN 0 AND 10',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object'     => 'custom_object',
                'type'       => 'int',
                'operator'   => 'between',
                'properties' => [
                    'filter' => [
                        'number_from' => 0,
                        'number_to'   => 10,
                    ],
                ],
            ]
        );

        $this->assertMatchWhere(
            'test_value.value >= :pard',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object'     => 'custom_object',
                'type'       => 'date',
                'operator'   => 'gte',
                'properties' => [
                    'filter' => [
                        'dateTypeMode' => 'absolute',
                        'absoluteDate' => 'yesterday',
                    ],
                ],
            ],
            (new \DateTime('yesterday'))->format('Y-m-d')
        );

        $this->assertMatchWhere(
            'test_value.value <= :pare',
            [
                'glue'       => 'and',
                'field'      => 'cmf_'.$this->getFixtureById('custom_object_product')->getId(),
                'object'     => 'custom_object',
                'type'       => 'datetime',
                'operator'   => 'lte',
                'properties' => [
                    'filter' => [
                        'dateTypeMode' => 'absolute',
                        'absoluteDate' => 'tomorrow',
                    ],
                ],
            ],
            (new \DateTime('tomorrow'))->format('Y-m-d 23:59:59')
        );
    }

    protected function assertMatchWhere(string $expectedWhere, array $filter, ?string $expectedValue = null): void
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
        if ($expectedValue) {
            $this->assertSame($expectedValue, current($unionQueryContainer->current()->getParameters()));
        }
    }
}
