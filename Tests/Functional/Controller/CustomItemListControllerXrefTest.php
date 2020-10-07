<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class CustomItemListControllerXrefTest extends MauticMysqlTestCase
{
    public function testListCustomItem(): void
    {
        $customObject       = $this->createCustomObject();
        $customItemUnlinked = $this->createCustomItem($customObject, 'MacBook Pro');
        $customItemLinked   = $this->createCustomItem($customObject, 'MacBook Air');
        $this->em->persist(new CustomItemXrefCustomItem($customItemUnlinked, $customItemLinked));
        $this->em->flush();

        $this->assertResponse($customObject, $customItemLinked, 'customItem', $customItemUnlinked->getId(), 0);
        $this->assertResponse($customObject, $customItemUnlinked, 'customItem', $customItemUnlinked->getId(), 1);
    }

    public function testListContact(): void
    {
        $contact            = new Lead();
        $customObject       = $this->createCustomObject();
        $customItemUnlinked = $this->createCustomItem($customObject, 'MacBook Pro');
        $customItemLinked   = $this->createCustomItem($customObject, 'MacBook Air');
        $this->em->persist(new CustomItemXrefContact($customItemLinked, $contact));
        $this->em->persist($contact);
        $this->em->flush();

        $filterEntityId = (int) $contact->getId();
        $this->assertResponse($customObject, $customItemLinked, 'contact', $filterEntityId, 0);
        $this->assertResponse($customObject, $customItemUnlinked, 'contact', $filterEntityId, 1);
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

    private function assertResponse(CustomObject $customObject, CustomItem $customItem, string $entityType, int $filterEntityId, int $lookup): void
    {
        $uri     = sprintf('/s/custom/object/%s/item?filterEntityId=%s&filterEntityType=%s&lookup=%d', $customObject->getId(), $filterEntityId, $entityType, $lookup);
        $crawler = $this->client->request(Request::METHOD_GET, $uri);

        $tableCrawler = $crawler->filter('table');
        Assert::assertSame(1, $tableCrawler->count());

        $rowCrawler = $tableCrawler->filterXPath('.//tbody/tr');
        Assert::assertSame(1, $rowCrawler->count());

        $cellCrawler = $rowCrawler->filter('td');
        Assert::assertSame($customItem->getName(), trim($cellCrawler->eq(1)->text()));
        Assert::assertSame((string) $customItem->getId(), trim($cellCrawler->eq(2)->text()));
    }
}
