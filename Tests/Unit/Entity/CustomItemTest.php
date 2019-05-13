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

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Mautic\CategoryBundle\Entity\Category;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCompany;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use Mautic\LeadBundle\Entity\Company;

class CustomItemTest extends \PHPUnit_Framework_TestCase
{
    public function testClone(): void
    {
        $item = new CustomItem(new CustomObject());
        $item->setName('Item A');

        $clone = clone $item;

        $this->assertSame('Item A', $item->getName());
        $this->assertSame('Item A', $clone->getName());
    }

    public function testGettersSetters(): void
    {
        $object      = new CustomObject();
        $item        = new CustomItem($object);
        $category    = new Category();
        $companyXref = new CustomItemXrefCompany($item, new Company());
        $itemXref    = new CustomItemXrefCustomItem($item, new CustomItem($object));

        $item->setName('Item A');
        $item->setLanguage('Klingon');
        $item->setCategory($category);
        $item->addCompanyReference($companyXref);
        $item->addCustomItemReference($itemXref);

        $this->assertSame($object, $item->getCustomObject());
        $this->assertSame($category, $item->getCategory());
        $this->assertSame('Item A', $item->getName());
        $this->assertSame('Klingon', $item->getLanguage());
        $this->assertSame($companyXref, $item->getCompanyReferences()->get(0));
        $this->assertSame($itemXref, $item->getCustomItemReferences()->get(0));
    }

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
