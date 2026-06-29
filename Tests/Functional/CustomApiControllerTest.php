<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;

final class CustomApiControllerTest extends MauticMysqlTestCase
{
    private ?CustomItemRepository $customItemRepository;
    private ?CustomItemXrefContactRepository $customItemXrefContactRepository;
    private Lead $contact;
    private CustomItem $customItem;

    protected function setUp(): void
    {
        $this->configParams['custom_objects_enabled'] = true;

        parent::setUp();

        $this->customItemRepository             = self::$container->get('custom_item.repository');
        $this->customItemXrefContactRepository  = self::$container->get('custom_item.xref.contact.repository');

        // Create a Contact
        $this->contact = $this->createContact();
        // Create a Custom Object
        $customObject = $this->createCustomObject('Product', 'Products');
        // Create a Custom Item
        $this->customItem = $this->createCustomItem($customObject, 'Product 1');
        // Relate CI with Contact
        $this->createCustomItemXrefContact($this->customItem, $this->contact);
    }

    public function testDetachCustomItemFomContact(): void
    {
        $items = $this->customItemRepository->count([]);
        $links = $this->customItemXrefContactRepository->count([]);
        $this->assertEquals(1, $items);
        $this->assertEquals(1, $links);

        $this->client->request('DELETE', sprintf('/api/custom/item/%s/unlink/%s', $this->customItem->getId(), $this->contact->getId()));
        $this->assertTrue($this->client->getResponse()->isOk());

        $items = $this->customItemRepository->count([]);
        $links = $this->customItemXrefContactRepository->count([]);
        $this->assertEquals(1, $items);
        $this->assertEquals(0, $links);
    }

    public function testDeleteCustomItem(): void
    {
        $items = $this->customItemRepository->count([]);
        $links = $this->customItemXrefContactRepository->count([]);
        $this->assertEquals(1, $items);
        $this->assertEquals(1, $links);

        $this->client->request('DELETE', sprintf('/api/custom/item/%s/delete', $this->customItem->getId()));
        $this->assertTrue($this->client->getResponse()->isOk());

        $items = $this->customItemRepository->count([]);
        $links = $this->customItemXrefContactRepository->count([]);
        $this->assertEquals(0, $items);
        $this->assertEquals(0, $links);
    }

    private function createCustomObject(string $singular, string $plural): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setNameSingular($singular);
        $customObject->setNamePlural($plural);
        $customObject->setAlias(mb_strtolower($plural));
        $this->em->persist($customObject);
        $this->em->flush();

        return $customObject;
    }

    private function createContact(): Lead
    {
        $lead = new Lead();
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createCustomItem(CustomObject $customObject, string $name): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setName($name);
        $this->em->persist($customItem);
        $this->em->flush();

        return $customItem;
    }

    private function createCustomItemXrefContact(CustomItem $customItem, Lead $contact): void
    {
        $customItemXrefContact = new CustomItemXrefContact($customItem, $contact);

        $this->em->persist($customItemXrefContact);
        $this->em->flush();
    }
}
