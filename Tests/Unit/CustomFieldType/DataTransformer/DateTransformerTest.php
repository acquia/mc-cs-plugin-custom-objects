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

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTransformer;

class DateTransformerTest extends \PHPUnit_Framework_TestCase
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
