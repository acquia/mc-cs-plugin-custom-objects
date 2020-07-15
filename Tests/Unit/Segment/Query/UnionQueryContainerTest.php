<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Segment\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;
use PHPUnit\Framework\TestCase;

class UnionQueryContainerTest extends TestCase
{
    /**
     * @var UnionQueryContainer
     */
    private $unionQueryContainer;

    /**
     * @var Connection
     */
    private $connection;

    public function setUp(): void
    {
        $this->unionQueryContainer = new UnionQueryContainer();
        $this->connection = new Connection(
            [],
            $this->createMock(Driver::class)
        );
        parent::setUp();
    }

    public function testWorkflow(): void
    {
        # Test no query
        $this->assertEquals(
            '',
            $this->unionQueryContainer->getMergedQueryString()
        );

        $this->assertEquals(
            [],
            $this->unionQueryContainer->getParameters()
        );

        $this->assertEquals(
            [],
            $this->unionQueryContainer->getParameterTypes()
        );

        # Test one query
        $qb = new SegmentQueryBuilder($this->connection);

        $qb->select('table_1')
            ->where('column1 > :param1')
            ->setParameter('param1', 1);

        $this->unionQueryContainer->addQuery($qb);

        $this->assertEquals(
            'SELECT table_1 WHERE column1 > :param1',
            $this->unionQueryContainer->getMergedQueryString()
        );

        $this->assertEquals(
            [
                'param1' => 1,
            ],
            $this->unionQueryContainer->getParameters()
        );

        $this->assertEquals(
            [],
            $this->unionQueryContainer->getParameterTypes()
        );

        # Test two queries

        $qb = new SegmentQueryBuilder($this->connection);
        $qb->select('table_2')
            ->where('column2 = :param2')
            ->setParameter('param2', [2, 3], Connection::PARAM_INT_ARRAY);

        $this->unionQueryContainer->addQuery($qb);

        $this->assertEquals(
            'SELECT table_1 WHERE column1 > :param1 UNION ALL SELECT table_2 WHERE column2 = :param2',
            $this->unionQueryContainer->getMergedQueryString()
        );

        $this->assertEquals(
            [
                'param1' => 1,
                'param2' => [
                    0 => 2,
                    1 => 3,
                ]
            ],
            $this->unionQueryContainer->getParameters()
        );

        $this->assertEquals(
            [
                'param2' => Connection::PARAM_INT_ARRAY
            ],
            $this->unionQueryContainer->getParameterTypes()
        );
    }
}
