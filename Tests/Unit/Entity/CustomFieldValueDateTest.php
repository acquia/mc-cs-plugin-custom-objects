<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDate;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomFieldValueDateTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $value       = new \DateTimeImmutable('2019-06-25');
        $customField = new CustomField();
        $customItem  = new CustomItem(new CustomObject());
        $optionValue = new CustomFieldValueDate($customField, $customItem, $value);

        $this->assertSame($customField, $optionValue->getCustomField());
        $this->assertSame($customItem, $optionValue->getCustomItem());
        $this->assertSame($value, $optionValue->getValue());

        $optionValue->setValue(null);

        $this->assertNull($optionValue->getValue());

        $optionValue->setValue('');

        $this->assertNull($optionValue->getValue());

        $optionValue->setValue($value);

        $this->assertSame($value, $optionValue->getValue());

        $optionValue->setValue('2019-06-23');

        $this->assertSame('2019-06-23T00:00:00+00:00', $optionValue->getValue()->format(DATE_ATOM));
    }
}
