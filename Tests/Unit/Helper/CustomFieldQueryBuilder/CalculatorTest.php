<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper\CustomFieldQueryBuilder;

use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory\Calculator;
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
        $level          = 2;
        $expectedMatrix = [
            '0',
            '1',
        ];

        $this->calculator->init($level);
        $this->assertEquals(2, $this->calculator->getTotalQueryCount());
        $this->assertEquals(1, $this->calculator->getJoinCountPerQuery());
        $this->assertMatrixEquals($level, $expectedMatrix, $this->calculator);

        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->getSuffixByIterator(2);
    }

    public function test3LevelMatrix(): void
    {
        $level          = 3;
        $expectedMatrix = [
            '00',
            '01',
            '10',
            '11',
        ];

        $this->calculator->init($level);
        $this->assertEquals(4, $this->calculator->getTotalQueryCount());
        $this->assertEquals(2, $this->calculator->getJoinCountPerQuery());
        $this->assertMatrixEquals($level, $expectedMatrix, $this->calculator);
    }

    public function test4LevelMatrix(): void
    {
        $level          = 4;
        $expectedMatrix = [
            '000',
            '001',
            '010',
            '011',
            '100',
            '101',
            '110',
            '111',
        ];

        $this->calculator->init($level);
        $this->assertEquals(8, $this->calculator->getTotalQueryCount());
        $this->assertEquals(3, $this->calculator->getJoinCountPerQuery());
        $this->assertMatrixEquals($level, $expectedMatrix, $this->calculator);
    }

    public function test5LevelMatrix(): void
    {
        $level          = 5;
        $expectedMatrix = [
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
        ];

        $this->calculator->init($level);
        $this->assertEquals(16, $this->calculator->getTotalQueryCount());
        $this->assertEquals(4, $this->calculator->getJoinCountPerQuery());
        $this->assertMatrixEquals($level, $expectedMatrix, $this->calculator);
    }

    private function assertMatrixEquals(int $level, array $expectedMatrix, Calculator $calculator)
    {
        $expectedMatrix = implode('', $expectedMatrix);

        for ($i = 0; $i < strlen($expectedMatrix); ++$i) {
            $decisionValue  = (bool) $expectedMatrix[$i];
            $expectedSuffix = $decisionValue ? self::COLUMN_SUFFIX_HIGHER : self::COLUMN_SUFFIX_LOWER;
            $this->assertEquals($expectedSuffix, $calculator->getSuffixByIterator($i));
        }
    }

    private function getSuffix($decisionValue): string
    {
        return $decisionValue ? self::COLUMN_SUFFIX_HIGHER : self::COLUMN_SUFFIX_LOWER;
    }
}
