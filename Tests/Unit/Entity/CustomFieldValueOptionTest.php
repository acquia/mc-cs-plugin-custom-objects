<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomFieldValueOptionTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $customObject = new CustomObject();
        $customField  = new CustomField();
        $value1       = 'red';
        $value2       = 'blue';
        $customItem   = new CustomItem($customObject);
        $optionValue  = new CustomFieldValueOption($customField, $customItem, $value1);

        $this->assertSame($customField, $optionValue->getCustomField());
        $this->assertSame($customItem, $optionValue->getCustomItem());
        $this->assertSame($value1, $optionValue->getValue());

        $optionValue->setValue($value2);

        $this->assertSame($value2, $optionValue->getValue());

        $optionValue = new CustomFieldValueOption($customField, $customItem);

        $optionValue->addValue($value1);
        $optionValue->addValue($value1); // Test uniqueness.
        $optionValue->addValue($value2);

        $this->assertSame([$value1, $value2], $optionValue->getValue());
    }

    public function testSetValueMustHaveUniqueOptions(): void
    {
        $optionValue = new CustomFieldValueOption(
            new CustomField(),
            new CustomItem(new CustomObject())
        );

        $optionValue->setValue(['red', 'blue', 'red']);

        $this->assertSame(['red', 'blue'], $optionValue->getValue());
    }
}
