<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemExportScheduler;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomObjectTestCase extends MauticMysqlTestCase
{
    protected function createCustomObject(string $name = 'custom object'): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setAlias($name);
        $customObject->setNameSingular($name);
        $customObject->setNamePlural($name);

        return $customObject;
    }

    protected function createCustomField(string $type, CustomObject $customObject): CustomField
    {
        $customFieldFactory = self::$container->get('custom_object.custom_field_factory');
        $customField        = $customFieldFactory->create('text', $customObject);
        $customField->setLabel($type);
        $customField->setAlias($type);

        return $customField;
    }

    /**
     * @param array<string,string> $data
     *
     * @throws \MauticPlugin\CustomObjectsBundle\Exception\NotFoundException
     */
    protected function createCustomItem(CustomObject $customObject, array $data, string $name): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setCustomFieldValues($data);
        $customItem->setName($name);

        return $customItem;
    }

    protected function createContact(string $firstName = 'name', string $lastName = 'second'): Lead
    {
        $contact =  new Lead();
        $contact->setFirstname($firstName);
        $contact->setLastname($lastName);

        return $contact;
    }

    protected function createCustomItemExportScheduleEntity(CustomObject $customObject): CustomItemExportScheduler
    {
        $customItemExportScheduler = new CustomItemExportScheduler();
        $customItemExportScheduler->setScheduledDateTime(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        $customItemExportScheduler->setUser($this->em->getRepository(User::class)->find(1));
        $customItemExportScheduler->setCustomObjectId((int) $customObject->getId());

        $this->em->persist($customItemExportScheduler);
        $this->em->flush();

        return $customItemExportScheduler;
    }
}
