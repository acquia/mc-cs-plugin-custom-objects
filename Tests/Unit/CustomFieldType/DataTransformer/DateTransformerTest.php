<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType\DataTransformer;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTransformer;

class DateTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function test(): void
    {
        $date     = '2019-05-30';
        $datetime = new \DateTime($date);
        $format   = 'Y-m-d';

        $transformer = new DateTransformer();

        $this->assertNull($transformer->transform(null));
        $this->assertSame($date, $transformer->transform($date)->format($format));

        $this->assertNull($transformer->reverseTransform(null));
        $this->assertSame($date, $transformer->reverseTransform($date)); // Transform string
        $this->assertSame($date, $transformer->reverseTransform($datetime)); // Transform DateTime object
    }
}
