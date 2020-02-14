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

use DateTime;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTimeAtomTransformer;

class DateTimeAtomTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function test(): void
    {
        $date     = '2019-05-30T13:45:04+00:00';
        $datetime = new DateTime($date);
        $format   = DATE_ATOM;

        $transformer = new DateTimeAtomTransformer();

        $this->assertNull($transformer->transform(null));
        $this->assertSame($date, $transformer->transform($date)->format($format));

        $this->assertNull($transformer->reverseTransform(null));
        $this->assertSame($date, $transformer->reverseTransform($date));
        $this->assertSame($date, $transformer->reverseTransform($datetime));
    }
}
