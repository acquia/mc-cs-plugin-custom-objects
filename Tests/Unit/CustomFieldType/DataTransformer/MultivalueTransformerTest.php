<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType\DataTransformer;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\MultivalueTransformer;

class MultivalueTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function test(): void
    {
        $value            = [1, 2];
        $transformedValue = json_encode($value);

        $transformer = new MultivalueTransformer();

        $this->assertSame([], $transformer->transform(null));
        $this->assertSame($value, $transformer->transform($transformedValue));

        $this->assertNull($transformer->reverseTransform(null));
        $this->assertSame($transformedValue, $transformer->reverseTransform($value));
    }
}
