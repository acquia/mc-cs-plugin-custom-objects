<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
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
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;

class ImportSubscriberTest extends KernelTestCase
{
    use DatabaseSchemaTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->container = static::$kernel->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $this->createFreshDatabaseSchema($entityManager);
    }

    /**
     * @todo Test fail on empty required field.
     * @todo Test creating contact links.
     * @todo Test validation of each field type fails the import.
     */
    public function testImportForCreated(): void
    {
        $customObject = $this->createCustomObjectWithAllFields();

        $rowData = [
            'name'     => 'Mautic CI Test',
            // 'contacts' => '3262739,3262738,3262737',
        ];

        $mappedFields = [
            'name'     => 'customItemName',
            // 'contacts' => 'linkedContactIds',
        ];

        $customObject->getCustomFields()->map(function (CustomField $customField) use (&$mappedFields, &$rowData): void {
            $key                = $customField->getTypeObject()->getKey();
            $mappedFields[$key] = (string) $customField->getId();

            if ($customField->isChoiceType()) {
                $optionA = new CustomFieldOption();
                $optionA->setCustomField($customField);
                $optionA->setLabel('Option A');
                $optionA->setValue('option_a');
                $customField->addOption($optionA);

                $optionB = new CustomFieldOption();
                $optionB->setCustomField($customField);
                $optionB->setLabel('Option B');
                $optionB->setValue('option_b');
                $customField->addOption($optionB);
            }

            switch ($customField->getType()) {
                case 'date':
                    $rowData[$key] = '2019-03-04';

                    break;
                case 'datetime':
                    $rowData[$key] = '2019-03-04 12:35:09';

                    break;
                case 'email':
                    $rowData[$key] = 'john@doe.email';

                    break;
                case 'int':
                    $rowData[$key] = '45433';

                    break;
                default:
                    if ($customField->canHaveMultipleValues()) {
                        $rowData[$key] = 'option_a,option_b';
                    } elseif ($customField->isChoiceType()) {
                        $rowData[$key] = 'option_b';
                    } else {
                        $rowData[$key] = 'Some text';
                    }

                    break;
            }
        });

        /** @var CustomObjectModel $customObjectModel */
        $customObjectModel = $this->container->get('mautic.custom.model.object');

        /** @var CustomItemImportModel $customItemImportModel */
        $customItemImportModel = $this->container->get('mautic.custom.model.import.item');

        $configProvider     = $this->createMock(ConfigProvider::class);
        $permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $importSubscriber   = new ImportSubscriber(
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
        $importProcessevent = new ImportProcessEvent($import, $leadEventLog, $rowData);

        $import->setMatchedFields($mappedFields);
        $import->setObject("custom-object:{$customObject->getId()}");
        $importSubscriber->onImportProcess($importProcessevent);
    }

    private function createCustomObjectWithAllFields(): CustomObject
    {
        /** @var CustomObjectModel $customObjectModel */
        $customObjectModel = $this->container->get('mautic.custom.model.object');
        $customObject      = new CustomObject();

        /** @var CustomFieldTypeProvider $customFieldTypeProvider */
        $customFieldTypeProvider = $this->container->get('custom_field.type.provider');
        $customFieldTypes        = $customFieldTypeProvider->getTypes();

        $customObject->setNameSingular('Import CI test');
        $customObject->setNamePlural('Import CI tests');

        foreach ($customFieldTypes as $customFieldType) {
            $customField = new CustomField();
            $customField->setTypeObject($customFieldType);
            $customField->setType($customFieldType->getKey());
            $customField->setLabel("{$customFieldType->getName()} Test Field");
            $customObject->addCustomField($customField);
        }

        $customObjectModel->save($customObject);

        return $customObject;
    }
}
