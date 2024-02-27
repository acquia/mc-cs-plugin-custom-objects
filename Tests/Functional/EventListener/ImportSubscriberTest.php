<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use DateTimeImmutable;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\ImportSubscriber;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportSubscriberTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

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
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Import CI all fields test Custom Object');
        $csvRow       = [
            'name'           => 'Import CI all fields test Custom Item',
            'contacts'       => "{$jane->getId()},{$john->getId()}",
            'text'           => 'Some text value',
            'country'        => 'Czech Republic',
            'datetime'       => '2019-03-04 12:35:09',
            'date'           => '2019-03-04',
            'email'          => 'info@doe.corp',
            'hidden'         => 'secret hidden text',
            'int'            => '3453562',
            'multiselect'    => 'option_b,option_a',
            'phone'          => '+420775603019',
            'select'         => 'option_b',
            'textarea'       => "Some looong\ntext\n\nhere",
            'url'            => 'https://mautic.org',
        ];

        $expectedValues                   = $csvRow;
        $expectedValues['datetime']       = new DateTimeImmutable('2019-03-04 12:35:09');
        $expectedValues['date']           = new DateTimeImmutable('2019-03-04 00:00:00');
        $expectedValues['multiselect']    = ['option_b', 'option_a'];

        // Import the custom item
        $insertStatus = $this->importCsvRow($customObject, $csvRow);

        $this->assertFalse($insertStatus);

        $this->em->clear();

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
        $expectedUpdatedValues['datetime']       = new DateTimeImmutable('2019-03-04 12:35:09');
        $expectedUpdatedValues['date']           = new DateTimeImmutable('2019-05-24 00:00:00');
        $expectedUpdatedValues['multiselect']    = ['option_b', 'option_a'];

        $this->assertTrue($updateStatus);

        $this->em->clear();

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

    public function testImportWithDefaultValues(): void
    {
        $configureFieldCallback = function (CustomField $customField): void {
            if ('date' === $customField->getType()) {
                $customField->setDefaultValue('2019-06-21');
            }
            if ('text' === $customField->getType()) {
                $customField->setDefaultValue('A default value');
            }
            if ('multiselect' === $customField->getType()) {
                $customField->setDefaultValue(['option_b']);
            }
            if ('hidden' === $customField->getType()) {
                $customField->setDefaultValue('Secret default');
            }
        };

        $jane         = $this->createContact('jane@doe.email');
        $john         = $this->createContact('john@doe.email');
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Import CI all fields test Custom Object', $configureFieldCallback);
        $csvRow       = [
            'name'           => 'Import CI all fields test Custom Item',
            'contacts'       => "{$jane->getId()},{$john->getId()}",
            // 'text'           => '', // Missing on purpose so the default value could kick in.
            // 'multiselect'    => '', // Missing on purpose so the default value could kick in.
            'country'        => 'Czech Republic',
            // 'date'           => '', // Missing on purpose so the default value could kick in.
            'datetime'       => '2019-03-04 12:35:09',
            'email'          => 'info@doe.corp',
            'hidden'         => 'secret hidden text', // Ensure the default value can be overwritten when provided.
            'int'            => '3453562',
            'phone'          => '+420775603019',
            'select'         => 'option_b',
            'textarea'       => "Some looong\ntext\n\nhere",
            'url'            => 'https://mautic.org',
        ];

        $expectedValues                   = $csvRow;
        $expectedValues['datetime']       = new DateTimeImmutable('2019-03-04 12:35:09');
        $expectedValues['date']           = new DateTimeImmutable('2019-06-21 00:00:00');
        $expectedValues['multiselect']    = ['option_b'];
        $expectedValues['text']           = 'A default value';

        // Import the custom item
        $insertStatus = $this->importCsvRow($customObject, $csvRow);

        $this->assertFalse($insertStatus);

        $this->em->clear();

        // Fetch the imported custom item
        $insertedCustomItem = $this->getCustomItemByName($csvRow['name']);

        $this->assertInstanceOf(CustomItem::class, $insertedCustomItem);
        $this->assertSame($customObject->getCustomFields()->count(), $insertedCustomItem->getCustomFieldValues()->count());
        $this->assertSame(2, $insertedCustomItem->getContactReferences()->count());

        $customObject->getCustomFields()->map(function (CustomField $customField) use ($insertedCustomItem, $expectedValues): void {
            $valueEntity = $insertedCustomItem->getCustomFieldValues()->get($customField->getId());
            $this->assertEquals($expectedValues[$customField->getType()], $valueEntity->getValue(), "For field {$customField->getAlias()}");
        });
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param string[] $csvRow
     */
    private function validateSelectOptionValueExists(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['multiselect'] = 'unicorn';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertStringContainsString("Value 'unicorn' does not exist in the list of options of field 'Multiselect Test Field' (multiselect-test-field). Possible values: option_a,option_b", $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param string[] $csvRow
     */
    private function validateDateValue(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['datetime'] = 'unicorn';
        $csvRow['date']     = 'unicorn';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertStringContainsString('Failed to parse time string (unicorn)', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param string[] $csvRow
     */
    private function validateEmail(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['email'] = 'bogus.@email';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertSame('\'bogus.@email\' is not a valid email address.', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param string[] $csvRow
     */
    private function validatePhone(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['phone'] = '+420111222333';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertStringContainsString('\'+420111222333\' is not a valid phone number. Use the following international phone number format [+][country code][subscriber number] for this field (eg: ‪+14028650000)', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param string[] $csvRow
     */
    private function validateUrl(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['url'] = 'unicorn';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertStringContainsString('\'unicorn\' is not a valid URL address. Maybe you forgot to add the protocol like https://?', $e->getMessage());
        }
    }

    /**
     * Try to save a multiselect option that does not exist.
     *
     * @param string[] $csvRow
     */
    private function validateNameCannotBeEmpty(CustomObject $customObject, array $csvRow): void
    {
        $csvRow['name'] = '';

        try {
            $this->importCsvRow($customObject, $csvRow);
        } catch (InvalidValueException $e) {
            $this->assertStringContainsString('This value should not be blank.', $e->getMessage());
        }
    }

    /**
     * @param string[] $csvRow
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
            if (array_key_exists($customField->getType(), $csvRow)) {
                $rowData[$key]      = $csvRow[$customField->getType()];
            }
        });

        /** @var CustomObjectModel $customObjectModel */
        $customObjectModel = self::$container->get('mautic.custom.model.object');

        /** @var CustomItemImportModel $customItemImportModel */
        $customItemImportModel = self::$container->get('mautic.custom.model.import.item');

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = self::$container->get('custom_field.repository');

        /** @var TranslatorInterface $translator */
        $translator = self::$container->get('translator');

        $configProvider     = $this->createMock(ConfigProvider::class);
        $permissionProvider = $this->createMock(CustomItemPermissionProvider::class);

        $importSubscriber = new ImportSubscriber(
            $customObjectModel,
            $customItemImportModel,
            $configProvider,
            $permissionProvider,
            $customFieldRepository,
            $translator
        );

        $configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $permissionProvider->expects($this->once())
            ->method('canCreate')
            ->with($customObject->getId());

        $import             = new Import();
        $leadEventLog       = new LeadEventLog();
        $importProcessEvent = new ImportProcessEvent($import, $leadEventLog, $rowData);

        $import->setMatchedFields($mappedFields);
        $import->setObject("custom-object:{$customObject->getId()}");

        $importSubscriber->onImportProcess($importProcessEvent);

        return $importProcessEvent->wasMerged();
    }

    private function getCustomItemByName(string $name): ?CustomItem
    {
        /** @var CustomItemRepository $customItemRepository */
        $customItemRepository = self::$container->get('custom_item.repository');

        /** @var CustomItemModel $customItemModel */
        $customItemModel = self::$container->get('mautic.custom.model.item');

        /** @var CustomItem $customItem */
        $customItem = $customItemRepository->findOneBy(['name' => $name]);

        if (!$customItem) {
            return null;
        }

        return $customItemModel->populateCustomFields($customItem);
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
