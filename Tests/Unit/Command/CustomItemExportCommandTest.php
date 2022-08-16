<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Command;

use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemExportScheduler;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CustomItemExportCommandTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @var CustomFieldFactory
     */
    private $customFieldFactory;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    private string $customItemExportSchedulerIds;

    /**
     * @var CustomObject
     */
    private CustomObject $customObject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldFactory = self::$container->get('custom_object.custom_field_factory');
        $this->customItemModel = self::$container->get('mautic.custom.model.item');

        $this->createMockData();
    }

    public function testCustomItemExport(): void
    {
        $commandTester = $this->getCustomItemExportCommandTester();
        $this->em->clear();

        $commandTester->execute(
            [
                '--ids' => $this->customItemExportSchedulerIds,
            ]
        );

        Assert::assertEquals(0, $commandTester->getStatusCode());
    }

    private function getCustomItemExportCommandTester()
    {
        $kernel      = self::$kernel;
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $command       = $application->find('mautic:custom_items:scheduled_export');

        return new CommandTester($command);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createMockData(): void
    {
        $customObject = $this->createCustomObject();
        $this->em->persist($customObject);
        $this->em->flush();

        $this->customObject = $customObject;

        $customField = $this->createCustomField('text', $customObject);
        $this->em->persist($customField);
        $this->em->flush();

        $this->customObject->addCustomField($customField);
        $this->em->flush();

        $data = [$customField->getAlias() => $customField->getAlias()];

        $total_custom_items = 2;

        $contacts = [];

        $total_linked_contacts = 2;

        for ($i = 0; $i < $total_linked_contacts; $i++) {
            $contact = $this->createContact();
            $this->em->persist($contact);
            $this->em->flush();

            $contacts[] = $contact;
        }


        for ($i = 0; $i<$total_custom_items; $i++) {
            $customItem = $this->createCustomItem($customObject, $data, "custom_item_".$i);
            $this->em->persist($customItem);
            $this->em->flush();

            foreach($contacts as $contact) {
                $this->customItemModel->linkEntity($customItem, 'contact', (int)$contact->getId());
            }
        }

        $customItemExportScheduler = $this->createCustomItemExportScheduleEntity();
        $this->customItemExportSchedulerIds = (string) $customItemExportScheduler->getId();
    }

    /**
     * @param string $type
     * @param CustomObject $customObject
     * @return CustomField
     */
    private function createCustomField(string $type, CustomObject $customObject): CustomField
    {
        $customField = $this->customFieldFactory->create('text', $customObject);
        $customField->setLabel($type);
        $customField->setAlias($type);

        return $customField;
    }

    /**
     * @param string $name
     * @return CustomObject
     */
    private function createCustomObject(string $name = "custom object"): CustomObject
    {
        $customObject = new CustomObject();
        $customObject->setAlias($name);
        $customObject->setNameSingular($name);
        $customObject->setNamePlural($name);

        return $customObject;
    }

    /**
     * @param CustomObject $customObject
     * @param array $data
     * @return CustomItem
     * @throws \MauticPlugin\CustomObjectsBundle\Exception\NotFoundException
     */
    private function createCustomItem(CustomObject $customObject, array $data, string $name): CustomItem
    {
        $customItem = new CustomItem($customObject);
        $customItem->setCustomFieldValues($data);
        $customItem->setName($name);

        return $customItem;
    }

    /**
     * @param string $firstName
     * @param string $secondName
     * @return Lead
     */
    private function createContact(string $firstName = "name", string $lastName = "second"): Lead
    {
       $contact =  new Lead();
       $contact->setFirstname($firstName);
       $contact->setLastname($lastName);

       return $contact;
    }

    private function createCustomItemExportScheduleEntity(): CustomItemExportScheduler
    {
        $customItemExportScheduler = new CustomItemExportScheduler();
        $customItemExportScheduler->setScheduledDateTime(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        $customItemExportScheduler->setUser($this->em->getRepository(User::class)->find(1));
        $customItemExportScheduler->setCustomObjectId((int) $this->customObject->getId());

        $this->em->persist($customItemExportScheduler);
        $this->em->flush();

        return $customItemExportScheduler;
    }
}
