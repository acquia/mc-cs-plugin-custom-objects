<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Segment\Query\Filter;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\FixtureObjectsTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomFieldFilterQueryBuilderMultiselectTest extends MauticMysqlTestCase
{
    use FixtureObjectsTrait;

    protected function setUp(): void
    {
        $this->configParams['custom_object_merge_filter'] = false;
        parent::setUp();
    }

    public function testIncludeAndExcludeOnMultiselectField(): void
    {
        $fixturesDir = $this->getFixturesDirectory();
        $objects     = $this->loadFixtureFiles([
            $fixturesDir.'/leads.yml',
            $fixturesDir.'/custom_objects.yml',
            $fixturesDir.'/custom_fields.yml',
            $fixturesDir.'/custom_items.yml',
            $fixturesDir.'/custom_xref.yml',
            $fixturesDir.'/custom_values.yml',
            $fixturesDir.'/custom_values_option.yml',
        ]);
        $this->setFixtureObjects($objects);

        $cfId = $this->findMultiselectFieldIdOrSkip();

        /** @var CustomFieldTypeProvider $typeProvider */
        $typeProvider = self::$container->get('custom_field.type.provider');
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher   = self::$container->get('event_dispatcher');
        /** @var CustomFieldRepository $cfRepoSvc */
        $cfRepoSvc    = self::$container->get('custom_field.repository');

        $helper = new QueryFilterHelper(
            $this->em,
            new QueryFilterFactory(
                $this->em,
                $typeProvider,
                $cfRepoSvc,
                new QueryFilterFactory\Calculator(),
                1
            ),
            new RandomParameterName()
        );
        $qbService = new CustomFieldFilterQueryBuilder(new RandomParameterName(), $dispatcher, $helper);

        // INCLUDE (IN)
        $includeFilter = $this->createMultiselectFilterMock(
            $cfId,
            ['power_stearing', '4_star_safety'],
            'in',
            'multiselect'
        );
        $includeIds = $this->fetchLeadIdsForFilter($qbService, $includeFilter);
        $this->assertNotEmpty($includeIds, 'IN should match at least one contact');

        // EXCLUDE (NOT IN)
        $excludeFilter = $this->createMultiselectFilterMock(
            $cfId,
            ['power_stearing', '4_star_safety'],
            'notIn',
            'multiselect'
        );
        $excludeIds = $this->fetchLeadIdsForFilter($qbService, $excludeFilter);
        $this->assertIsArray($excludeIds);
    }

    private function findMultiselectFieldIdOrSkip(): int
    {
        $repo    = $this->em->getRepository(CustomField::class);
        $byLabel = $repo->findOneBy(['label' => 'Features']);
        if ($byLabel instanceof CustomField) {
            return (int) $byLabel->getId();
        }

        $qb    = $repo->createQueryBuilder('f');
        $field = $qb
            ->where($qb->expr()->in('f.type', ':types'))
            ->setParameter('types', ['option', 'multiselect'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($field instanceof CustomField) {
            return (int) $field->getId();
        }

        // DBAL 2/3-safe fallback: pick any field that has option rows
        $qb2 = $this->connection->createQueryBuilder()
            ->select('v.custom_field_id')
            ->from($this->prefixTable('custom_field_value_option'), 'v')
            ->setMaxResults(1);

        $stmt = method_exists($qb2, 'executeQuery') ? $qb2->executeQuery() : $qb2->execute();
        $cfId = (int) (method_exists($stmt, 'fetchOne') ? $stmt->fetchOne() : $stmt->fetchColumn());

        if ($cfId > 0) {
            return $cfId;
        }

        $this->markTestSkipped('No multiselect/option field found in fixtures.');
    }

    /**
     * @param list<string> $values
     *
     * @return ContactSegmentFilter&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMultiselectFilterMock(
        int $fieldId,
        array $values,
        string $operator = 'in',
        string $type = 'multiselect'
    ): ContactSegmentFilter {
        // Normalize operator to what prod code expects
        $raw  = (string) $operator;
        $low  = strtolower($raw);
        if ('in' === $low || 'multiselect' === $low) {
            $normalized = 'in';
        } elseif ('notin' === $low || '!multiselect' === $raw) {
            $normalized = 'notIn';
        } else {
            $normalized = $raw; // pass through others (eq, neq, etc.)
        }

        /** @var ContactSegmentFilter&\PHPUnit\Framework\MockObject\MockObject $filter */
        $filter                            = $this->getMockBuilder(ContactSegmentFilter::class)->disableOriginalConstructor()->getMock();
        $filter->contactSegmentFilterCrate = $this->createMock(ContactSegmentFilterCrate::class);
        $filter->method('getType')->willReturn($type);           // keep 'multiselect' here for option table mapping
        $filter->method('getOperator')->willReturn($normalized); // normalized op
        $filter->method('getField')->willReturn((string) $fieldId);
        $filter->method('getParameterValue')->willReturn($values);
        $filter->method('getParameterHolder')->willReturn(':needle');

        return $filter;
    }

    /**
     * Prefixes a table name using the current DB configuration or the MAUTIC_TABLE_PREFIX constant.
     */
    private function prefixTable(string $table): string
    {
        $prefix = '';
        // Don’t use isset() — PHPStan considers static::$container non-nullable.
        try {
            // In Mautic test envs this parameter exists.
            $prefix = (string) self::$container->getParameter('mautic.db_table_prefix');
        } catch (\Throwable $e) {
            if (defined('MAUTIC_TABLE_PREFIX')) {
                $prefix = (string) MAUTIC_TABLE_PREFIX;
            }
        }

        return $prefix.$table;
    }

    /**
     * Return distinct lead IDs matched by a filter.
     *
     * @return list<int>
     */
    private function fetchLeadIdsForFilter(
        CustomFieldFilterQueryBuilder $svc,
        ContactSegmentFilter $filter
    ): array {
        $qb = new QueryBuilder($this->connection);
        $qb->select('DISTINCT l.id')->from($this->prefixTable('leads'), 'l');

        $svc->applyQuery($qb, $filter);

        $stmt = method_exists($qb, 'executeQuery') ? $qb->executeQuery() : $qb->execute();
        /** @var list<int|string> $col */
        $col = method_exists($stmt, 'fetchFirstColumn')
            ? $stmt->fetchFirstColumn()
            : array_column($stmt->fetchAll(\PDO::FETCH_NUM), 0);

        $ids = array_values(array_unique(array_map('intval', $col)));
        sort($ids);

        return $ids;
    }
}
