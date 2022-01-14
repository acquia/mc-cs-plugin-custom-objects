<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Controller;

use DateTime;
use InvalidArgumentException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\AbstractCustomFieldValue;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDate;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class CustomItemListControllerShownFieldTest extends MauticMysqlTestCase
{
    /**
     * @var CustomFieldFactory
     */
    private $fieldFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldFactory = self::$container->get('custom_object.custom_field_factory');
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'custom_item',
            'custom_object',
        ]);
    }

    public function testCustomItemDetailList(): void
    {
        $this->assertDetailListDisplaysFields('customItem');
    }

    public function testContactDetailList(): void
    {
        $this->assertDetailListDisplaysFields('contact');
    }

    private function assertDetailListDisplaysFields(string $type): void
    {
        switch ($type) {
            case 'customItem':
                $relatedObject    = $this->createCustomObject('Device', 'Devices');
                $customItemDevice = $this->createCustomItem($relatedObject, 'Latitude E7000');
                $createXref       = function (CustomItem $customItem) use ($customItemDevice) {
                    $this->em->persist(new CustomItemXrefCustomItem($customItemDevice, $customItem));
                };
                $showInCustomObjectDetailList = false;
                $showInContactDetailList      = true;
                break;
            case 'contact':
                $relatedObject = $this->createLead();
                $createXref    = function (CustomItem $customItem) use ($relatedObject) {
                    $this->em->persist(new CustomItemXrefContact($customItem, $relatedObject));
                };
                $showInCustomObjectDetailList = true;
                $showInContactDetailList      = false;
                break;
            default:
                throw new InvalidArgumentException();
                break;
        }

        $customObjectAnimal = $this->createCustomObject('Animal', 'Animals');
        $customItemAnimal   = $this->createCustomItem($customObjectAnimal, 'Dog');

        $customFieldBreed       = $this->createCustomField($customObjectAnimal, 'text', 'Breed');
        $customFieldAge         = $this->createCustomField($customObjectAnimal, 'int', 'Age');
        $customFieldBirthDate   = $this->createCustomField($customObjectAnimal, 'date', 'Date of birth');
        $customFieldNotShown    = $this->createCustomField($customObjectAnimal, 'text', 'Not shown');
        $customFieldInoculation = $this->createCustomField($customObjectAnimal, 'datetime', 'Inoculation');
        $customFieldDiseases    = $this->createCustomField($customObjectAnimal, 'multiselect', 'Diseases');

        $this->createCustomFieldOption($customFieldDiseases, 'Flue', 'flue', 0);
        $optionDiseaseFever = $this->createCustomFieldOption($customFieldDiseases, 'Fever', 'fever', 1);

        $fieldValueBasset      = $this->addCustomFieldValue($customItemAnimal, new CustomFieldValueText($customFieldBreed, $customItemAnimal, 'Basset'));
        $fieldValueAge         = $this->addCustomFieldValue($customItemAnimal, new CustomFieldValueInt($customFieldAge, $customItemAnimal, 5));
        $fieldValueBirthDate   = $this->addCustomFieldValue($customItemAnimal, new CustomFieldValueDate($customFieldBirthDate, $customItemAnimal, new DateTime('2015-02-15')));
        $fieldValueInoculation = $this->addCustomFieldValue($customItemAnimal, new CustomFieldValueDateTime($customFieldInoculation, $customItemAnimal, new DateTime('2020-09-15')));
        $this->addCustomFieldValue($customItemAnimal, new CustomFieldValueOption($customFieldDiseases, $customItemAnimal, 'fever'));

        $createXref($customItemAnimal);
        $customFieldNotShown->setShowInCustomObjectDetailList($showInCustomObjectDetailList);
        $customFieldNotShown->setShowInContactDetailList($showInContactDetailList);

        $this->em->flush();

        $crawler      = $this->client->request(Request::METHOD_GET, sprintf('/s/custom/object/%s/item?filterEntityId=%s&filterEntityType=%s', $customObjectAnimal->getId(), $relatedObject->getId(), $type));
        $tableCrawler = $crawler->filterXPath(sprintf('//h3[contains(text(), "%s")]/following::table[1]', $customObjectAnimal->getNameSingular()));
        Assert::assertSame(1, $tableCrawler->count());

        $headRowCrawler = $tableCrawler->filterXPath('.//thead/tr');
        Assert::assertSame(1, $headRowCrawler->count());

        $cellCrawler = $headRowCrawler->filter('th');
        $this->assertCellValue(1, 'Name', $cellCrawler);
        $this->assertCellValue(2, 'ID', $cellCrawler);
        $this->assertCellValue(3, $customFieldBreed->getLabel(), $cellCrawler);
        $this->assertCellValue(4, $customFieldAge->getLabel(), $cellCrawler);
        $this->assertCellValue(5, $customFieldBirthDate->getLabel(), $cellCrawler);
        $this->assertCellValue(6, $customFieldInoculation->getLabel(), $cellCrawler);
        $this->assertCellValue(7, $customFieldDiseases->getLabel(), $cellCrawler);

        $bodyRowCrawler = $tableCrawler->filterXPath('.//tbody/tr');
        Assert::assertSame(1, $bodyRowCrawler->count());

        $cellCrawler = $bodyRowCrawler->filter('td');
        $this->assertCellValue(1, $customItemAnimal->getName(), $cellCrawler);
        $this->assertCellValue(2, (string) $customItemAnimal->getId(), $cellCrawler);
        $this->assertCellValue(3, $fieldValueBasset->getValue(), $cellCrawler);
        $this->assertCellValue(4, (string) $fieldValueAge->getValue(), $cellCrawler);
        $this->assertCellValue(5, $fieldValueBirthDate->getValue()->format('F j, Y'), $cellCrawler);
        $this->assertCellValue(6, $fieldValueInoculation->getValue()->format('F j, Y g:i a T'), $cellCrawler);
        $this->assertCellValue(7, $optionDiseaseFever->getValue(), $cellCrawler);
    }

    private function createCustomObject(string $singular, string $plural): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setNameSingular($singular);
        $customObject->setNamePlural($plural);
        $customObject->setAlias(mb_strtolower($plural));
        $this->em->persist($customObject);

        return $customObject;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $this->em->persist($lead);

        return $lead;
    }

    private function createCustomField(CustomObject $customObject, string $type, string $label): CustomField
    {
        $customField = $this->fieldFactory->create($type, $customObject);
        $customField->setLabel($label);
        $customField->setAlias(mb_strtolower($label));
        $customObject->addCustomField($customField);

        return $customField;
    }

    private function createCustomItem(CustomObject $customObject, string $name): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setName($name);
        $this->em->persist($customItem);

        return $customItem;
    }

    private function createCustomFieldOption(CustomField $customField, string $label, string $value, int $order): CustomFieldOption
    {
        $customFieldOption = new CustomFieldOption();
        $customFieldOption->setCustomField($customField);
        $customFieldOption->setLabel($label);
        $customFieldOption->setValue($value);
        $customFieldOption->setOrder($order);
        $this->em->persist($customFieldOption);

        return $customFieldOption;
    }

    private function addCustomFieldValue(CustomItem $customItem, AbstractCustomFieldValue $customFieldValue): AbstractCustomFieldValue
    {
        $customItem->addCustomFieldValue($customFieldValue);
        $this->em->persist($customFieldValue);

        return $customFieldValue;
    }

    private function assertCellValue(int $column, string $expected, Crawler $cellCrawler): void
    {
        Assert::assertSame($expected, trim($cellCrawler->eq($column)->text()));
    }
}
