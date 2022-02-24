<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType\DataTransformer;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTimeTransformer;

class DateTimeTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function test(): void
    {
        $date     = '2019-05-30 13:45';
        $datetime = new \DateTime($date);
        $format   = 'Y-m-d H:i';

        $transformer = new DateTimeTransformer();

        $this->assertNull($transformer->transform(null));
        $this->assertSame($date, $transformer->transform($date)->format($format));

        $this->assertNull($transformer->reverseTransform(null));
        $this->assertSame($date, $transformer->reverseTransform($date));
        $this->assertSame($date, $transformer->reverseTransform($datetime));
    }
}
