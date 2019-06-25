<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomFieldValueDateTimeTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters(): void
    {
        $value       = new \DateTimeImmutable('2019-06-25 12:43:33');
        $customField = new CustomField();
        $customItem  = new CustomItem(new CustomObject());
        $optionValue = new CustomFieldValueDateTime($customField, $customItem, $value);

        $this->assertSame($customField, $optionValue->getCustomField());
        $this->assertSame($customItem, $optionValue->getCustomItem());
        $this->assertSame($value, $optionValue->getValue());

        $optionValue->setValue(null);

        $this->assertNull($optionValue->getValue());

        $optionValue->setValue('');

        $this->assertNull($optionValue->getValue());

        $optionValue->setValue($value);

        $this->assertSame($value, $optionValue->getValue());

        $optionValue->setValue('2019-06-23 12:43:33');

        $this->assertSame('2019-06-23T12:43:33+00:00', $optionValue->getValue()->format(DATE_ATOM));
    }
}
