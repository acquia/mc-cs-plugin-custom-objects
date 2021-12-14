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

class CustomItemLookupControllerTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetAutoincrement([
            'custom_item',
            'leads',
        ]);
    }

    public function testListCustomItem(): void
    {
        $customObject       = $this->createCustomObject();
        $customItemUnlinked = $this->createCustomItem($customObject, 'MacBook Pro');
        $customItemLinked   = $this->createCustomItem($customObject, 'MacBook Air');
        $this->em->persist(new CustomItemXrefCustomItem($customItemUnlinked, $customItemLinked));
        $this->em->flush();

        $this->assertResponse($customObject, $customItemUnlinked, 'customItem', 'mac');
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

        $this->assertResponse($customObject, $customItemUnlinked, 'contact', 'mac');
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

    private function assertResponse(CustomObject $customObject, CustomItem $customItem, string $entityType, string $filter): void
    {
        $uri = sprintf('/s/custom/object/%s/item/lookup.json?filterEntityId=%s&filterEntityType=%s&filter=%s', $customObject->getId(), $customItem->getId(), $entityType, $filter);
        $this->client->request(Request::METHOD_GET, $uri);

        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        Assert::assertJson($content);

        $data = json_decode($content, true);
        Assert::assertIsArray($data);
        Assert::assertArrayHasKey('items', $data);

        $items = $data['items'];
        Assert::assertIsArray($items);
        Assert::assertIsArray($items);
        Assert::assertCount(1, $items);

        $item = reset($items);
        Assert::assertArrayHasKey('id', $item);
        Assert::assertSame($customItem->getId(), (int) $item['id']);
        Assert::assertArrayHasKey('value', $item);
        Assert::assertSame(sprintf('%s (%s)', $customItem->getName(), $customItem->getId()), $item['value']);
    }
}
