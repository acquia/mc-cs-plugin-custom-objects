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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType\DataTransformer;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\CsvTransformer;

class CsvTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function test(): void
    {
        $transformer = new CsvTransformer();

        $this->assertSame('', $transformer->transform(null));
        $this->assertSame('', $transformer->transform([]));
        $this->assertSame('some,array,"contains, comma"', $transformer->transform(['some', 'array', 'contains, comma']));
        $this->assertSame('some string', $transformer->transform('some string'));

        $this->assertSame([], $transformer->reverseTransform(null));
        $this->assertSame([], $transformer->reverseTransform(''));
        $this->assertSame(['some', 'array', 'contains, comma'], $transformer->reverseTransform('some,array,"contains, comma"'));
        $this->assertSame(['some', 'array'], $transformer->reverseTransform(['some', 'array']));
    }
}
