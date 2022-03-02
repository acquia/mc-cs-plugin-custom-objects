<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory;

/**
 * Parameter and join combination query counter.
 */
class Calculator
{
    private const COLUMN_SUFFIX_LOWER  = 'lower';
    private const COLUMN_SUFFIX_HIGHER = 'higher';

    /**
     * @var int
     */
    private $level;

    /**
     * Matrix ciphers - joins per query.
     *
     * @var int
     */
    private $cipherCount;

    /**
     * Number of union queries to be generated.
     *
     * @var int
     */
    private $totalQueryCountPerLevel;

    /**
     * @var string
     */
    private $matrix;

    /**
     * Reset counter with new level.
     */
    public function init(int $level): void
    {
        $this->level       = $level;
        $this->cipherCount = $this->level - 1;

        $highestCombinationNumberBin   = str_repeat('1', $this->cipherCount);
        $this->totalQueryCountPerLevel = bindec($highestCombinationNumberBin) + 1;

        $this->calculateMatrix();
    }

    /**
     * Number of union queries to be generated.
     */
    public function getTotalQueryCount(): int
    {
        return $this->totalQueryCountPerLevel;
    }

    public function getJoinCountPerQuery(): int
    {
        return $this->cipherCount;
    }

    public function getSuffixByIterator(int $i): string
    {
        if (isset($this->matrix[$i])) {
            $decisionValue = (bool) $this->matrix[$i];

            return $decisionValue ? self::COLUMN_SUFFIX_HIGHER : self::COLUMN_SUFFIX_LOWER;
        }

        throw new \InvalidArgumentException("Value '$i' is out of generated matrix");
    }

    public function getOppositeSuffix(string $suffix): string
    {
        return (self::COLUMN_SUFFIX_LOWER === $suffix) ? self::COLUMN_SUFFIX_HIGHER : self::COLUMN_SUFFIX_LOWER;
    }

    private function calculateMatrix(): void
    {
        $this->matrix = '';

        for ($i = 0; $i < $this->totalQueryCountPerLevel; ++$i) {
            $this->matrix .= $this->dec2bin($i);
        }
    }

    private function dec2bin(int $value): string
    {
        $value              = decbin($value);
        $missingCipherCount = $this->cipherCount - strlen($value);

        if ($missingCipherCount) {
            $value = str_repeat('0', $missingCipherCount).$value;
        }

        return $value;
    }
}
