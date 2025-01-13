<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\CustomObjectsBundle\Entity\AbstractCustomFieldValue;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class CustomItemListControllerSearchTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testSearchItemName(): void
    {
        $customObject    = $this->createCustomObject();
        $customItemFound = $this->createCustomItem($customObject, 'Latitude E7000');
        $this->createCustomItem($customObject, 'MacBook Pro');
        $this->em->flush();

        $this->assertFound($customObject, $customItemFound, 'E700');
    }

    public function testSearchFieldValueText(): void
    {
        $customObject       = $this->createCustomObject();
        $customItemNotFound = $this->createCustomItem($customObject, 'MacBook Pro');
        $customItemFound    = $this->createCustomItem($customObject, 'Latitude E7000');

        $customField = new CustomField();
        $customField->setLabel('Brand');
        $customField->setType('text');
        $customField->setAlias('brand');
        $customObject->addCustomField($customField);

        $this->addCustomFieldValue($customItemNotFound, new CustomFieldValueText($customField, $customItemNotFound, 'Apple'));
        $this->addCustomFieldValue($customItemFound, new CustomFieldValueText($customField, $customItemFound, 'Dell Inc.'));

        $this->em->flush();

        $this->assertFound($customObject, $customItemFound, 'inc');
    }

    public function testSearchFieldValueOption(): void
    {
        $customObject       = $this->createCustomObject();
        $customItemNotFound = $this->createCustomItem($customObject, 'MacBook Pro');
        $customItemFound    = $this->createCustomItem($customObject, 'Latitude E7000');

        $customField = new CustomField();
        $customField->setLabel('Size');
        $customField->setType('multiselect');
        $customField->setAlias('size');
        $customObject->addCustomField($customField);

        $this->createCustomFieldOption($customField, 'Small size', 'size small', 0);
        $this->createCustomFieldOption($customField, 'Medium size', 'size medium', 1);

        $this->addCustomFieldValue($customItemNotFound, new CustomFieldValueOption($customField, $customItemNotFound, 'size small'));
        $this->addCustomFieldValue($customItemFound, new CustomFieldValueOption($customField, $customItemFound, 'size medium'));

        $this->em->flush();

        $this->assertFound($customObject, $customItemFound, 'medi');
    }

    private function createCustomObject(): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setNameSingular('Device');
        $customObject->setNamePlural('Devices');
        $customObject->setAlias('devices');
        $this->em->persist($customObject);

        return $customObject;
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

    private function addCustomFieldValue(CustomItem $customItem, AbstractCustomFieldValue $customFieldValue): void
    {
        $customItem->addCustomFieldValue($customFieldValue);
        $this->em->persist($customFieldValue);
    }

    private function assertFound(CustomObject $customObject, CustomItem $customItem, string $search): void
    {
        $crawler      = $this->client->request(Request::METHOD_GET, sprintf('/s/custom/object/%s/item?search=%s', $customObject->getId(), $search));
        $tableCrawler = $crawler->filterXPath('//h1[contains(text(), "Devices")]/following::table[1]');
        Assert::assertSame(1, $tableCrawler->count());

        $rowCrawler = $tableCrawler->filterXPath('.//tbody/tr');
        Assert::assertSame(1, $rowCrawler->count());

        $cellCrawler = $rowCrawler->filter('td');
        Assert::assertSame($customItem->getName(), trim($cellCrawler->eq(1)->text()));
        Assert::assertSame((string) $customItem->getId(), trim($cellCrawler->eq(2)->text()));
    }
}
