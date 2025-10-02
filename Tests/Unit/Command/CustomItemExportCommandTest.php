<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Command;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomObjectTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CustomItemExportCommandTest extends CustomObjectTestCase
{
    protected $useCleanupRollback = false;

    private CustomItemModel $customItemModel;

    private string $customItemExportSchedulerIds;

    private CustomObject $customObject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel = self::$container->get('mautic.custom.model.item');

        $this->createMockData();
    }

    public function testCustomItemExport(): void
    {
        $this->client->enableProfiler();

        $commandTester = $this->getCustomItemExportCommandTester();
        $this->em->clear();

        $commandTester->execute(
            [
                '--ids' => $this->customItemExportSchedulerIds,
            ]
        );

        $this->assertEquals(0, $commandTester->getStatusCode());
        $outputMessage = $commandTester->getDisplay();
        $this->assertStringContainsString('CustomItem export email(s) sent: 1', $outputMessage);
    }

    /**
     * @return CommandTester
     */
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

        for ($i = 0; $i < $total_linked_contacts; ++$i) {
            $contact = $this->createContact();
            $this->em->persist($contact);
            $this->em->flush();

            $contacts[] = $contact;
        }

        for ($i = 0; $i < $total_custom_items; ++$i) {
            $customItem = $this->createCustomItem($customObject, $data, 'custom_item_'.$i);
            $this->em->persist($customItem);
            $this->em->flush();

            foreach ($contacts as $contact) {
                $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());
            }
        }

        $customItemExportScheduler          = $this->createCustomItemExportScheduleEntity($this->customObject);
        $this->customItemExportSchedulerIds = (string) $customItemExportScheduler->getId();
    }
}
