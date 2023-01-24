<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpsertFunctionalTest extends \Mautic\CoreBundle\Test\MauticMysqlTestCase
{
    use CustomObjectsTrait;

    private CustomItem $existingCustomItem;
    private ?object $customItemModel;
    private CustomObject $customObject;
    private ?object $customItemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel       = self::$container->get('mautic.custom.model.item');
        $this->customItemRepository  = self::$container->get('custom_item.repository');
        $this->customObject          = $this->createCustomObject();
        $this->existingCustomItem    = $this->createCustomItem($this->customObject, 'Sapiens');
        $this->existingCustomItem->createNewCustomFieldValueByFieldAlias('unique_id_field', 'SomeUniqueHash');
        $this->existingCustomItem = $this->customItemModel->save($this->existingCustomItem);
    }

    public function testCreateNewCustomItemWithUniqueHash(): void
    {
        $this->assertEquals(1, $this->customItemRepository->count(['customObject' => $this->customObject->getId()]));
        $this->assertEquals('Sapiens', $this->existingCustomItem->getName());

        $newCustomItem = $this->createCustomItem($this->customObject, 'Factfulness');
        $newCustomItem->createNewCustomFieldValueByFieldAlias('unique_id_field', 'SomeOtherUniqueHash');
        $newCustomItem = $this->customItemModel->save($newCustomItem);

        $this->assertTrue($newCustomItem->hasBeenInserted());
        $this->assertFalse($newCustomItem->hasBeenUpdated());
        $this->assertEquals(2, $this->customItemRepository->count(['customObject' => $this->customObject->getId()]));
        $this->assertNotEquals($this->existingCustomItem->getUniqueHash(), $newCustomItem->getUniqueHash());
        $this->assertEquals('Factfulness', $newCustomItem->getName());

        $duplicateCustomItem = $this->createCustomItem($this->customObject, 'Inferno');
        $duplicateCustomItem->createNewCustomFieldValueByFieldAlias('unique_id_field', 'SomeUniqueHash');
        $duplicateCustomItem = $this->customItemModel->save($duplicateCustomItem);

        $this->assertTrue($duplicateCustomItem->hasBeenUpdated());
        $this->assertFalse($duplicateCustomItem->hasBeenInserted());
        $this->assertEquals(2, $this->customItemRepository->count(['customObject' => $this->customObject->getId()]));
        $this->assertNotEquals($duplicateCustomItem->getUniqueHash(), $newCustomItem->getUniqueHash());
    }

    private function createCustomItem(CustomObject $customObject, string $name): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setName($name);

        return $customItem;
    }

    private function createCustomObject(): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setNameSingular('Book');
        $customObject->setNamePlural('Books');
        $customObject->setAlias('books');
        $this->em->persist($customObject);
        $this->createCustomField($customObject);

        return $customObject;
    }

    private function createCustomField(CustomObject $customObject): CustomField
    {
        $translatorMock             = $this->createMock(TranslatorInterface::class);
        $filterOperatorProviderMock = $this->createMock(FilterOperatorProviderInterface::class);
        $customFieldType            = new TextType($translatorMock, $filterOperatorProviderMock);
        $customField                = new CustomField();
        $customField->setLabel('Unique ID field');
        $customField->setType('text');
        $customField->setTypeObject($customFieldType);
        $customField->setAlias('unique_id_field');
        $customField->setCustomObject($customObject);
        $customField->setIsUniqueIdentifier(true);
        $this->em->persist($customField);
        $customObject->setCustomFields(new ArrayCollection([$customField]));
        $this->em->persist($customObject);
        $this->em->flush();

        return $customField;
    }
}
