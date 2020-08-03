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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper\CustomFieldQueryBuilder;

use MauticPlugin\CustomObjectsBundle\Helper\CustomFieldQueryBuilder\Calculator;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    private const COLUMN_SUFFIX_LOWER  = 'lower';
    private const COLUMN_SUFFIX_HIGHER = 'higher';

    /**
     * @var Calculator
     */
    private $calculator;

    public function setUp(): void
    {
        $this->calculator = new Calculator();
    }

    public function test2LevelMatrix(): void
    {
        $matrix = implode(
            '',
            [
                '0',
                '1',
            ]
        );

        $this->calculator->init(2);
        $this->assertEquals(2, $this->calculator->getTotalQueryCount());
        $this->assertEquals(1, $this->calculator->getJoinCountPerQuery());
    }

    public function test3LevelMatrix(): void
    {
        $matrix = implode(
            '',
            [
                '00',
                '01',
                '10',
                '11',
            ]
        );

        $this->calculator->init(3);
        $this->assertEquals(4, $this->calculator->getTotalQueryCount());
        $this->assertEquals(2, $this->calculator->getJoinCountPerQuery());
    }

    public function test4LevelMatrix(): void
    {
        $matrix = implode(
            '',
            [
                '000',
                '001',
                '010',
                '011',
                '100',
                '101',
                '110',
                '111',
            ]
        );

        $this->calculator->init(4);
        $this->assertEquals(8, $this->calculator->getTotalQueryCount());
        $this->assertEquals(3, $this->calculator->getJoinCountPerQuery());
    }

    public function test5LevelMatrix(): void
    {
        $matrix = implode(
            '',
            [
                '0000',
                '0001',
                '0010',
                '0011',
                '0100',
                '0101',
                '0110',
                '0111',
                '1000',
                '1001',
                '1010',
                '1011',
                '1100',
                '1101',
                '1110',
                '1111',
            ]
        );

        $this->calculator->init(5);
        $this->assertEquals(16, $this->calculator->getTotalQueryCount());
        $this->assertEquals(4, $this->calculator->getJoinCountPerQuery());
    }
}
