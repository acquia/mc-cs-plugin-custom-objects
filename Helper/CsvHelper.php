<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Helper;

class CsvHelper
{
    /**
     * @param mixed[] $row
     *
     * @return string
     */
    public function arrayToCsvLine(array $row, $delimiter = ',', $enclosure = '"', $escapeChar = '\\'): string
    {
        $resource = fopen('php://memory', 'r+');

        fputcsv($resource, $row, $delimiter, $enclosure, $escapeChar);
        rewind($resource);
        $csvLine = stream_get_contents($resource);
        fclose($resource);

        return rtrim($csvLine);
    }

    /**
     * @param string $row
     *
     * @return string[]
     */
    public static function csvLineToArray(string $row): array
    {
        if (empty($row)) {
            return [];
        }

        return str_getcsv($row);
    }
}
