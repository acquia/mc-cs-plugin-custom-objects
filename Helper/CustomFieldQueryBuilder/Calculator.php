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

namespace MauticPlugin\CustomObjectsBundle\Helper\CustomFieldQueryBuilder;

/**
 * Parameter combination query counter
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
     * Matrix ciphers - joins per query
     *
     * @var int
     */
    private $cipherCount;

    /**
     * Number of union queries to be generated
     *
     * @var int
     */
    private $totalQueryCountPerLevel;

    /**
     * @var string
     */
    private $matrix;

    /**
     * Reset counter with new level
     *
     * @param int $level
     */
    public function init(int $level): void
    {
        $this->level = $level;
        $this->cipherCount = $this->level - 1;
        $this->totalQueryCountPerLevel = $this->getTotalQueryCount();
    }

    /**
     * Number of union queries to be generated
     */
    public function getTotalQueryCount(): int
    {
        if ($this->totalQueryCountPerLevel) {
            return $this->totalQueryCountPerLevel;
        }

        $highestCombinationNumberBin = str_repeat('1', $this->cipherCount);

        return bindec($highestCombinationNumberBin) + 1;
    }

    public function getJoinCountPerQuery(): int
    {
        return $this->level - 1;
    }

    public function getSuffixByIterator(int $i): string
    {
        if (isset($this->matrix[$i])) {
            return $this->matrix[$i];
        }

        throw new \InvalidArgumentException("Value '$i' is out of generated matrix");
    }

    public function getComputedSuffix(int $totalIterator, int $joinIterator): string
    {
        $totalIteratorBin = decbin($totalIterator);
        $missingCipherCount = $this->cipherCount - strlen($totalIteratorBin);

        if ($missingCipherCount) {
            $totalIteratorBin = str_repeat("0", $missingCipherCount) . $totalIteratorBin;
        }

        $decisionValue = (bool) $totalIteratorBin[($this->cipherCount - $joinIterator-1)]; // 0/1 = true/false
        // Translate to suffix
        return $decisionValue ? self::COLUMN_SUFFIX_HIGHER : self::COLUMN_SUFFIX_LOWER;
    }

    public function getOppositeSuffix(string $suffix): string
    {
        return ($suffix === self::COLUMN_SUFFIX_LOWER) ? self::COLUMN_SUFFIX_HIGHER : self::COLUMN_SUFFIX_LOWER;
    }

    private function computeMatrix(): array
    {
        $this->matrix = '';

        for($i = 1; $i <= $this->totalQueryCountPerLevel; $i++) {
            $this->matrix .= $this->dec2bin($i++);
        }
    }

    private function dec2bin(int $number): string
    {
        $value = decbin($value);


    }


}