<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;

class CsvHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testArrayToCsvLine(): void
    {
        $csvHelper = new CsvHelper();

        $this->assertSame('', $csvHelper->arrayToCsvLine([]));
        $this->assertSame('0,ff,,-4,,1,"text, with; ""comma"""', $csvHelper->arrayToCsvLine([0, 'ff', null, -4, false, true, 'text, with; "comma"']));
    }

    public function testCsvLineToArray(): void
    {
        $csvHelper = new CsvHelper();

        $this->assertSame([], $csvHelper->csvLineToArray(''));
        $this->assertSame(['0', 'ff', '', '-4', '', '1', 'text, with; \"comma\"'], $csvHelper->csvLineToArray('0,ff,,-4,,1, "text, with; \"comma\""'));
    }
}
