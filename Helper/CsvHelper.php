<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

class CsvHelper
{
    /**
     * @param mixed[] $row
     */
    public function arrayToCsvLine(array $row, string $delimiter = ',', string $enclosure = '"', string $escapeChar = '\\'): string
    {
        $resource = fopen('php://memory', 'r+');

        fputcsv($resource, $row, $delimiter, $enclosure, $escapeChar);
        rewind($resource);
        $csvLine = stream_get_contents($resource);
        fclose($resource);

        return rtrim($csvLine);
    }

    /**
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
