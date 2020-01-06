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
