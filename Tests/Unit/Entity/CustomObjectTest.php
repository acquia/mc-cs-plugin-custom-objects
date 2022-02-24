<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CategoryBundle\Entity\Category;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CustomObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testClone(): void
    {
        $object = new CustomObject();
        $object->setAlias('object-a');
        $object->setNameSingular('Object A');
        $clone = clone $object;

        $this->assertNull($clone->getAlias());
        $this->assertSame('Object A', $clone->getNameSingular());
    }

    public function testLoadValidatorMetadata(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $object   = new CustomObject();

        $metadata->expects($this->exactly(6))
            ->method('addPropertyConstraint')
            ->withConsecutive(
                ['alias', $this->isInstanceOf(Length::class)],
                ['nameSingular', $this->isInstanceOf(NotBlank::class)],
                ['nameSingular', $this->isInstanceOf(Length::class)],
                ['namePlural', $this->isInstanceOf(NotBlank::class)],
                ['namePlural', $this->isInstanceOf(Length::class)],
                ['description', $this->isInstanceOf(Length::class)]
            );

        $object->loadValidatorMetadata($metadata);
    }

    public function testGettersSetters(): void
    {
        $category = new Category();
        $object   = new CustomObject();
        $fields   = new ArrayCollection();

        $object->setCategory($category);
        $this->assertSame($category, $object->getCategory());
        $object->setLanguage('sk');
        $this->assertSame('sk', $object->getLanguage());
        $object->setCustomFields($fields);
        $this->assertSame($fields, $object->getCustomFields());
        $object->setType(1);
        $this->assertSame(1, $object->getType());

        $co = $this->createMock(CustomObject::class);
        $object->setMasterObject($co);
        $this->assertSame($co, $object->getMasterObject());
    }

    public function testCustomFieldChanges(): void
    {
        $object        = new CustomObject();
        $modifiedField = $this->createMock(CustomField::class);
        $createdField  = $this->createMock(CustomField::class);
        $deletedField  = $this->createMock(CustomField::class);

        $modifiedField->method('getId')->willReturn(13);
        $modifiedField->method('toArray')->willReturnOnConsecutiveCalls(
            ['id' => 13, 'label' => 'Field A'],        // initial value
            ['id' => 13, 'label' => 'Field A changed'] // new value
        );
        $createdField->method('toArray')->willReturn(['id' => null, 'label' => 'Field B']);
        $deletedField->method('toArray')->willReturn(['id' => 44, 'label' => 'Field C']);
        $deletedField->method('getId')->willReturn(44);

        $object->addCustomField($modifiedField);
        $object->addCustomField($deletedField);
        $object->createFieldsSnapshot();
        $object->addCustomField($createdField);
        $object->removeCustomField($deletedField);
        $object->recordCustomFieldChanges();

        $this->assertSame([
            'customfield:13:label' => [
                'Field A',
                'Field A changed',
            ],
            'customfield:temp_2:id' => [
                null,
                'temp_2',
            ],
            'customfield:temp_2:label' => [
                null,
                'Field B',
            ],
            'customfield:44' => [
                null,
                'deleted',
            ],
        ], $object->getChanges());
    }

    public function testGetPublishedFields(): void
    {
        $object           = new CustomObject();
        $publishedField   = $this->createMock(CustomField::class);
        $unpublishedField = $this->createMock(CustomField::class);

        $publishedField->method('isPublished')->willReturn(true);
        $unpublishedField->method('isPublished')->willReturn(false);

        $object->addCustomField($publishedField);
        $object->addCustomField($unpublishedField);

        $publishedFields = $object->getPublishedFields();

        $this->assertCount(1, $publishedFields);
        $this->assertSame($publishedField, $publishedFields->current());
    }

    public function testGetCfByOrder()
    {
        $object = new CustomObject();
        $field1 = $this->createMock(CustomField::class);
        $field2 = $this->createMock(CustomField::class);

        $field1->method('getOrder')->willReturn(1);
        $field2->method('getOrder')->willReturn(2);

        $object->addCustomField($field1);
        $object->addCustomField($field2);

        $this->assertSame($field2, $object->getCustomFieldByOrder(2));

        $this->expectException(NotFoundException::class);

        $object->getCustomFieldByOrder(3);
    }
}
