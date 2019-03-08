<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class CustomItemTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomFieldValueChanges(): void
    {
        $item   = new CustomItem(new CustomObject());
        $fieldA = $this->createMock(CustomField::class);
        $fieldB = $this->createMock(CustomField::class);
        $valueA = new CustomFieldValueText($fieldA, $item, 'a value A');
        $valueB = new CustomFieldValueText($fieldB, $item, 'a value B');

        $fieldA->method('getId')->willReturn(13);
        $fieldB->method('getId')->willReturn(55);

        $item->addCustomFieldValue($valueA);
        $item->createFieldValuesSnapshot();
        $item->addCustomFieldValue($valueB);
        $valueA->setValue('a changed value A');
        $item->recordCustomFieldValueChanges();

        $this->assertSame([
            'customfieldvalue:13' => [
                'a value A',
                'a changed value A',
            ],
            'customfieldvalue:55' => [
                null,
                'a value B',
            ],
        ], $item->getChanges());
    }
}
