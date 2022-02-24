<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType\DataTransformer;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\ViewDateTransformer;

class ViewDateTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const VAL = 'value';

    public function testTransform(): void
    {
        $transformer = new ViewDateTransformer();

        $this->assertNull($transformer->transform(''));
        $this->assertSame(self::VAL, $transformer->transform(self::VAL));
    }

    public function testReverseTransform(): void
    {
        $transformer = new ViewDateTransformer();

        $this->assertSame('', $transformer->reverseTransform(null));
        $this->assertSame(self::VAL, $transformer->reverseTransform(self::VAL));
    }
}
