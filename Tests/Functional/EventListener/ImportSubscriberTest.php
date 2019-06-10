<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\DatabaseSchemaTrait;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\EventListener\ImportSubscriber;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;

class ImportSubscriberTest extends KernelTestCase
{
    use DatabaseSchemaTrait;
    use CustomObjectsTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->container = static::$kernel->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager       = $this->container->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->createFreshDatabaseSchema($entityManager);
    }

    /**
     * Tests insert of 1 custom item.
     * Tests update of 1 custom item.
     * Tests creating contact links.
     *
     * @todo Test fail on empty required field.
     * @todo Test validation of each field type fails the import.
     * @todo Test validation that a choice type has the value key from the csv.
     */
    public function testImportForAllFieldTypesWithValidValuesAndLinksPlusUpdateToo(): void
    {
        $jane         = $this->createContact('jane@doe.email');
        $john         = $this->createContact('john@doe.email');
        $customObject = $this->createCustomObjectWithAllFields($this->container, 'Import CI all fields test Custom Object');
        $csvRow       = [
            'name'           => 'Import CI all fields test Custom Item',
            'contacts'       => "{$jane->getId()},{$john->getId()}",
            'text'           => 'Some text value',
            'checkbox_group' => 'option_b',
            'country'        => 'Czech Republic',
            'datetime'       => '2019-03-04 12:35:09',
            'date'           => '2019-03-04',
            'email'          => 'info@doe.corp',
            'hidden'         => 'secret hidden text',
            'int'            => '3453562',
            'multiselect'    => 'option_b,option_a',
            'phone'          => '+420775603019',
            'radio_group'    => 'option_a',
            'select'         => 'option_b',
            'textarea'       => "Some looong\ntext\n\nhere",
            'url'            => 'https://mautic.org',
        ];

        $expectedValues                   = $csvRow;
        $expectedValues['datetime']       = new \DateTimeImmutable('2019-03-04 12:35:09');
        $expectedValues['date']           = new \DateTimeImmutable('2019-03-04 00:00:00');
        $expectedValues['checkbox_group'] = ['option_b'];
        $expectedValues['multiselect']    = ['option_a', 'option_b'];

        // Import the custom item
        $insertStatus = $this->importCsvRow($customObject, $csvRow);

        $this->assertFalse($insertStatus);

        $this->entityManager->clear();

        // Fetch the imported custom item
        $insertedCustomItem = $this->getCustomItemByName($csvRow['name']);

        $this->assertInstanceOf(CustomItem::class, $insertedCustomItem);

        $insertId = $insertedCustomItem->getId();

        $this->assertSame($customObject->getCustomFields()->count(), $insertedCustomItem->getCustomFieldValues()->count());
        $this->assertSame(2, $insertedCustomItem->getContactReferences()->count());

        $customObject->getCustomFields()->map(function (CustomField $customField) use ($insertedCustomItem, $expectedValues): void {
            $valueEntity = $insertedCustomItem->getCustomFieldValues()->get($customField->getId());
            $this->assertEquals($expectedValues[$customField->getType()], $valueEntity->getValue());
        });

        $editCsvRow = $csvRow;

        // Update some values
        $editCsvRow['name'] = 'Import CI all fields test Custom Item - updated';
        $editCsvRow['date'] = '2019-05-24';
        $editCsvRow['url']  = 'https://mautic.com';
        $editCsvRow['id']   = (string) $insertedCustomItem->getId();

        // Update the custom item
        $updateStatus = $this->importCsvRow($customObject, $editCsvRow);

        $expectedUpdatedValues                   = $editCsvRow;
        $expectedUpdatedValues['datetime']       = new \DateTimeImmutable('2019-03-04 12:35:09');
        $expectedUpdatedValues['date']           = new \DateTimeImmutable('2019-05-24 00:00:00');
        $expectedUpdatedValues['checkbox_group'] = ['option_b'];
        $expectedUpdatedValues['multiselect']    = ['option_a', 'option_b'];

        $this->assertTrue($updateStatus);

        $this->entityManager->clear();

        // Fetch the imported custom item again
        $updatedCustomItem = $this->getCustomItemByName($editCsvRow['name']);

        $this->assertInstanceOf(CustomItem::class, $updatedCustomItem);

        $customObject->getCustomFields()->map(function (CustomField $customField) use ($updatedCustomItem, $expectedUpdatedValues): void {
            $valueEntity = $updatedCustomItem->getCustomFieldValues()->get($customField->getId());
            $this->assertEquals($expectedUpdatedValues[$customField->getType()], $valueEntity->getValue());
        });

        $this->assertSame($insertId, $updatedCustomItem->getId());

        // Lets do some validation tests with the same database to save some test runtime.
        $this->validateSelectOptionValueExists($customObject, $csvRow);
        $this->validateDateValue($customObject, $csvRow);
        $this->validateEmail($customObject, $csvRow);
        $this->validatePhone($customObject, $csvRow);
        $this->validateUrl($customObject, $csvRow);
        $this->validateNameCannotBeEmpty($customObject, $csvRow);
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param CustomObject $customObject
     * @param string[]     $csvRow
     */
    private function validateSelectOptionValueExists(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['multiselect'] = 'unicorn';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertContains("Value 'unicorn' does not exist in the list of options of field 'Multiselect Test Field' (11). Possible values: option_a, option_b", $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param CustomObject $customObject
     * @param string[]     $csvRow
     */
    private function validateDateValue(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['datetime'] = 'unicorn';
        $csvRow['date']     = 'unicorn';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertContains('Failed to parse time string (unicorn)', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param CustomObject $customObject
     * @param string[]     $csvRow
     */
    private function validateEmail(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['email'] = 'bogus.@email';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertSame('This value is not a valid email address.', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param CustomObject $customObject
     * @param string[]     $csvRow
     */
    private function validatePhone(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['phone'] = '+420111222333';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertContains('Please use the following international phone number format [+][country code][subscriber number] for this field (eg: ‪+14028650000).', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param CustomObject $customObject
     * @param string[]     $csvRow
     */
    private function validateUrl(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['url'] = 'unicorn';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertContains('This value is not a valid URL', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param CustomObject $customObject
     * @param string[]     $csvRow
     */
    private function validateNameCannotBeEmpty(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['name'] = '';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertContains('This value should not be blank.', $e->getMessage());
        }
    }

    /**
     * @param CustomObject $customObject
     * @param string[]     $csvRow
     *
     * @return bool
     */
    private function importCsvRow(CustomObject $customObject, array $csvRow): bool
    {
        $rowData      = [];
        $mappedFields = [];

        if (isset($csvRow['name'])) {
            $rowData['name']      = $csvRow['name'];
            $mappedFields['name'] = 'customItemName';
        }

        if (isset($csvRow['contacts'])) {
            $rowData['contacts']      = $csvRow['contacts'];
            $mappedFields['contacts'] = 'linkedContactIds';
        }

        if (isset($csvRow['id'])) {
            $rowData['id']      = $csvRow['id'];
            $mappedFields['id'] = 'customItemId';
        }

        $customObject->getCustomFields()->map(function (CustomField $customField) use (&$mappedFields, &$rowData, $csvRow): void {
            $key                = $customField->getTypeObject()->getKey();
            $mappedFields[$key] = (string) $customField->getId();
            $rowData[$key]      = $csvRow[$customField->getType()];
        });

        /** @var CustomObjectModel $customObjectModel */
        $customObjectModel = $this->container->get('mautic.custom.model.object');

        /** @var CustomItemImportModel $customItemImportModel */
        $customItemImportModel = $this->container->get('mautic.custom.model.import.item');
        $configProvider        = $this->createMock(ConfigProvider::class);
        $permissionProvider    = $this->createMock(CustomItemPermissionProvider::class);

        $importSubscriber = new ImportSubscriber(
            $customObjectModel,
            $customItemImportModel,
            $configProvider,
            $permissionProvider
        );

        $configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $permissionProvider->expects($this->once())
            ->method('canCreate')
            ->with($customObject->getId())
            ->willReturn(true);

        $import             = new Import();
        $leadEventLog       = new LeadEventLog();
        $importProcessEvent = new ImportProcessEvent($import, $leadEventLog, $rowData);

        $import->setMatchedFields($mappedFields);
        $import->setObject("custom-object:{$customObject->getId()}");

        $importSubscriber->onImportProcess($importProcessEvent);

        return $importProcessEvent->wasMerged();
    }

    /**
     * @param string $name
     *
     * @return CustomItem|mixed
     */
    private function getCustomItemByName(string $name)
    {
        /** @var CustomItemRepository $customItemRepository */
        $customItemRepository = $this->container->get('custom_item.repository');

        /** @var CustomItemModel $customItemModel */
        $customItemModel = $this->container->get('mautic.custom.model.item');

        /** @var CustomItem $customItem */
        $customItem = $customItemRepository->findOneBy(['name' => $name]);

        if (!$customItem) {
            return;
        }

        return $customItemModel->populateCustomFields($customItem);
    }

    /**
     * @param string $email
     *
     * @return Lead
     */
    private function createContact(string $email): Lead
    {
        /** @var LeadModel $contactModel */
        $contactModel = $this->container->get('mautic.lead.model.lead');
        $contact      = new Lead();
        $contact->setEmail($email);
        $contactModel->saveEntity($contact);

        return $contact;
    }
}
