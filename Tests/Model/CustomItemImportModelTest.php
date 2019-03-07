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

namespace MauticPlugin\CustomObjectsBundle\Tests\Model;

use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Entity\Import;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextareaType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;

class CustomItemImportModelTest extends \PHPUnit_Framework_TestCase
{
    private const ROW_DATA = [
        'name'        => 'Mautic Demo',
        'date'        => '2019-03-04',
        'description' => 'Showing demo of Mautic to potential clients.',
        'contacts'    => '3262739,3262738,3262737',
    ];

    private const MAPPED_FIELDS = [
        'contacts'    => 'linkedContactIds',
        'date'        => '34',
        'description' => '33',
        'name'        => 'customItemName',
    ];

    private $customObject;

    private $import;

    private $entityManager;

    private $customItemModel;

    private $customItemXrefContactModel;

    private $formatterHelper;

    private $customItemImportModel;

    private $descriptionField;

    private $dateField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->descriptionField           = $this->createMock(CustomField::class);
        $this->dateField                  = $this->createMock(CustomField::class);
        $this->customObject               = $this->createMock(CustomObject::class);
        $this->import                     = $this->createMock(Import::class);
        $this->customItemModel            = $this->createMock(CustomItemModel::class);
        $this->entityManager              = $this->createMock(EntityManager::class);
        $this->customItemXrefContactModel = $this->createMock(CustomItemXrefContactModel::class);
        $this->formatterHelper            = $this->createMock(FormatterHelper::class);
        $this->customItemImportModel      = new CustomItemImportModel(
            $this->entityManager,
            $this->customItemModel,
            $this->customItemXrefContactModel,
            $this->formatterHelper
        );

        $this->descriptionField->method('getId')->willReturn(33);
        $this->descriptionField->method('getTypeObject')->willReturn(new TextareaType('textarea'));
        $this->dateField->method('getId')->willReturn(34);
        $this->dateField->method('getTypeObject')->willReturn(new DateTimeType('date time'));
    }

    public function testImportForCreated(): void
    {
        $this->import->expects($this->exactly(2))
            ->method('getMatchedFields')
            ->willReturn(self::MAPPED_FIELDS);

        $this->formatterHelper->expects($this->once())
            ->method('simpleCsvToArray')
            ->with('3262739,3262738,3262737')
            ->willReturn([3262739, 3262738, 3262737]);

        $this->customObject->expects($this->exactly(2))
            ->method('getCustomFields')
            ->willReturn([$this->descriptionField, $this->dateField]);

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($this->callback(function (CustomItem $customItem) {
                $this->assertSame('Mautic Demo', $customItem->getName());
                $this->assertSame($this->customObject, $customItem->getCustomObject());
                $fieldValues = $customItem->getCustomFieldValues();
                $dateField = $fieldValues[0];
                $descriptionField = $fieldValues[1];
                $this->assertSame('2019-03-04', $dateField->getValue()->format('Y-m-d'));
                $this->assertSame($this->dateField, $dateField->getCustomField());
                $this->assertSame('Showing demo of Mautic to potential clients.', $descriptionField->getValue());
                $this->assertSame($this->descriptionField, $descriptionField->getCustomField());

                return true;
            }));

        $this->assertSame(
            false,
            $this->customItemImportModel->import($this->import, self::ROW_DATA, $this->customObject)
        );
    }
}
