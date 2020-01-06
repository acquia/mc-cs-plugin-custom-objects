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
