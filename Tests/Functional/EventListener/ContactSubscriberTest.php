<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use PHPUnit\Framework\Assert;

final class ContactSubscriberTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    private CustomItemModel $customItemModel;

    private ContactMerger $contactMerger;

    private CustomItemXrefContactRepository $customItemXrefContactRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel                 = self::$container->get('mautic.custom.model.item');
        $this->contactMerger                   = self::$container->get('mautic.lead.merger');
        $this->customItemXrefContactRepository = self::$container->get('custom_item.xref.contact.repository');
    }

    public function testMergingContactsWhenLoserHasItemAndWinnerHasNot(): void
    {
        $winner       = $this->createContact('john@doe.email');
        $loser        = $this->createContact('anna@muck.email');
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Campaign test object');
        $customItem   = new CustomItem($customObject);

        $customItem->setName('Campaign test item');
        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $loser->getId());

        $this->contactMerger->merge($winner, $loser);

        Assert::assertCount(0, $this->customItemXrefContactRepository->findBy(['contact' => $loser->getId()]));
        Assert::assertCount(1, $this->customItemXrefContactRepository->findBy(['contact' => $winner->getId()]));
    }

    public function testMergingContactsWhenLoserHasItemAndWinnerTheSameToo(): void
    {
        $winner       = $this->createContact('john@doe.email');
        $loser        = $this->createContact('anna@muck.email');
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Campaign test object');
        $customItem   = new CustomItem($customObject);

        $customItem->setName('Campaign test item');
        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $loser->getId());
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $winner->getId());

        $this->contactMerger->merge($winner, $loser);

        Assert::assertCount(0, $this->customItemXrefContactRepository->findBy(['contact' => $loser->getId()]));
        Assert::assertCount(1, $this->customItemXrefContactRepository->findBy(['contact' => $winner->getId()]));
    }

    private function createContact(string $email): Lead
    {
        /** @var LeadModel $contactModel */
        $contactModel = self::$container->get('mautic.lead.model.lead');
        $contact      = new Lead();
        $contact->setEmail($email);
        $contactModel->saveEntity($contact);

        return $contact;
    }
}
