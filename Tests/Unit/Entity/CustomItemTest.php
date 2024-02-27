<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCompany;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomItemTest extends \PHPUnit\Framework\TestCase
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
        $this->assertSame($itemXref, $item->getCustomItemLowerReferences()->get(0));
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

    public function testFindCustomFieldValueForFieldAlias(): void
    {
        $object = new CustomObject();
        $item   = new CustomItem($object);
        $field1 = $this->createMock(CustomField::class);
        $field2 = $this->createMock(CustomField::class);
        $value1 = new CustomFieldValueText($field1, $item, 'value1');
        $value2 = new CustomFieldValueText($field2, $item, 'value2');

        $field1->method('getAlias')->willReturn('field-alias-1');
        $field2->method('getAlias')->willReturn('field-alias-2');
        $field1->method('getId')->willReturn(1);
        $field2->method('getId')->willReturn(2);

        $object->addCustomField($field1);
        $object->addCustomField($field2);
        $item->addCustomFieldValue($value1);
        $item->addCustomFieldValue($value2);

        $this->assertSame($value2, $item->findCustomFieldValueForFieldAlias('field-alias-2'));
        $this->assertSame($value1, $item->findCustomFieldValueForFieldAlias('field-alias-1'));

        $this->expectException(NotFoundException::class);
        $item->findCustomFieldValueForFieldAlias('unicorn');
    }

    public function testCreateNewCustomFieldValueByFieldId(): void
    {
        $object = new CustomObject();
        $item   = new CustomItem($object);
        $field  = $this->createMock(CustomField::class);

        $field->method('getId')->willReturn(1);
        $field->method('getTypeObject')->willReturn(
            new TextType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $object->addCustomField($field);

        $value = $item->createNewCustomFieldValueByFieldId(1, 'value1');
        $this->assertSame($field, $value->getCustomField());
        $this->assertSame($item, $value->getCustomItem());
        $this->assertSame('value1', $value->getValue());
        $this->assertSame(1, $value->getId());

        $this->expectException(NotFoundException::class);
        $item->createNewCustomFieldValueByFieldId(2, 'unicorn');
    }

    public function testCreateNewCustomFieldValueByFieldAlias(): void
    {
        $object = new CustomObject();
        $item   = new CustomItem($object);
        $field  = $this->createMock(CustomField::class);

        $field->method('getAlias')->willReturn('field-alias-1');
        $field->method('getId')->willReturn(1);
        $field->method('getTypeObject')->willReturn(
            new TextType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $object->addCustomField($field);

        $value = $item->createNewCustomFieldValueByFieldAlias('field-alias-1', 'value1');
        $this->assertSame($field, $value->getCustomField());
        $this->assertSame($item, $value->getCustomItem());
        $this->assertSame('value1', $value->getValue());
        $this->assertSame(1, $value->getId());

        $this->expectException(NotFoundException::class);
        $item->createNewCustomFieldValueByFieldAlias('field-alias-2', 'unicorn');
    }

    public function testSetDefaultValuesForMissingFields(): void
    {
        $object = new CustomObject();
        $item   = new CustomItem($object);
        $fieldA = $this->createMock(CustomField::class);
        $fieldB = $this->createMock(CustomField::class);
        $fieldC = $this->createMock(CustomField::class);

        $fieldA->method('getId')->willReturn(1);
        $fieldA->expects($this->never())->method('getDefaultValue');
        $fieldA->method('getTypeObject')->willReturn(
            new TextType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $fieldB->method('getId')->willReturn(2);
        $fieldB->expects($this->once())->method('getDefaultValue')->willReturn('Default B');
        $fieldB->method('getTypeObject')->willReturn(
            new TextType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $fieldC->method('getId')->willReturn(3);
        $fieldB->expects($this->once())->method('getDefaultValue')->willReturn(null);
        $fieldC->method('getTypeObject')->willReturn(
            new TextType(
                $this->createMock(TranslatorInterface::class),
                $this->createMock(FilterOperatorProviderInterface::class)
            )
        );

        $object->addCustomField($fieldA);
        $object->addCustomField($fieldB);
        $object->addCustomField($fieldC);

        $item->addCustomFieldValue($item->createNewCustomFieldValueByFieldId(1, 'Value A'));

        $item->setDefaultValuesForMissingFields();

        $this->assertCount(3, $item->getCustomFieldValues());
    }
}
