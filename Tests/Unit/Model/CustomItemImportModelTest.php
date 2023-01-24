<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Templating\Helper\FormatterHelper;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextareaType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomItemImportModelTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @var MockObject|CustomObject
     */
    private $customObject;

    /**
     * @var MockObject|Import
     */
    private $import;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|CustomItemModel
     */
    private $customItemModel;

    /**
     * @var MockObject|FormatterHelper
     */
    private $formatterHelper;

    /**
     * @var MockObject|CustomField
     */
    private $descriptionField;

    /**
     * @var MockObject|CustomField
     */
    private $dateField;

    /**
     * @var MockObject|FilterOperatorProviderInterface
     */
    private $filterOperatorProvider;

    /**
     * @var CustomItemImportModel
     */
    private $customItemImportModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->descriptionField       = $this->createMock(CustomField::class);
        $this->dateField              = $this->createMock(CustomField::class);
        $this->customObject           = $this->createMock(CustomObject::class);
        $this->import                 = $this->createMock(Import::class);
        $this->customItemModel        = $this->createMock(CustomItemModel::class);
        $this->entityManager          = $this->createMock(EntityManager::class);
        $this->formatterHelper        = $this->createMock(FormatterHelper::class);
        $this->filterOperatorProvider = $this->createMock(FilterOperatorProviderInterface::class);
        $this->customItemImportModel  = new CustomItemImportModel(
            $this->entityManager,
            $this->customItemModel,
            $this->formatterHelper
        );

        /** @var TranslatorInterface $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        $textareaType = new TextareaType($translator, $this->filterOperatorProvider);
        $dateTimeType = new DateTimeType($translator, $this->filterOperatorProvider);
        $this->descriptionField->method('getId')->willReturn(33);
        $this->descriptionField->method('getTypeObject')->willReturn($textareaType);
        $this->dateField->method('getId')->willReturn(34);
        $this->dateField->method('getTypeObject')->willReturn($dateTimeType);
    }

    public function testImportForCreated(): void
    {
        $this->import->expects($this->exactly(2))
            ->method('getMatchedFields')
            ->willReturn(self::MAPPED_FIELDS);

        $this->customItemModel->expects($this->never())
            ->method('fetchEntity');

        $this->formatterHelper->expects($this->once())
            ->method('simpleCsvToArray')
            ->with('3262739,3262738,3262737')
            ->willReturn([3262739, 3262738, 3262737]);

        $this->customObject->expects($this->exactly(3))
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$this->descriptionField, $this->dateField]));

        $customItem = $this->createMock(CustomItem::class);

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($this->callback(function (CustomItem $customItem) {
                $this->assertSame('Mautic Demo', $customItem->getName());
                $this->assertSame($this->customObject, $customItem->getCustomObject());
                $fieldValues      = $customItem->getCustomFieldValues();
                $descriptionField = $fieldValues->get(33);
                $dateField        = $fieldValues->get(34);
                $this->assertSame('2019-03-04', $dateField->getValue()->format('Y-m-d'));
                $this->assertSame($this->dateField, $dateField->getCustomField());
                $this->assertSame('Showing demo of Mautic to potential clients.', $descriptionField->getValue());
                $this->assertSame($this->descriptionField, $descriptionField->getCustomField());

                return true;
            }))
            ->willReturn($customItem);

        $this->assertSame(
            false,
            $this->customItemImportModel->import($this->import, self::ROW_DATA, $this->customObject)
        );
    }

    public function testImportForCreatedWhenObjectHasNoFields(): void
    {
        $this->import->expects($this->exactly(2))
            ->method('getMatchedFields')
            ->willReturn(self::MAPPED_FIELDS);

        $this->formatterHelper->expects($this->once())
            ->method('simpleCsvToArray')
            ->with('3262739,3262738,3262737')
            ->willReturn([3262739, 3262738, 3262737]);

        $this->customObject->expects($this->exactly(1))
            ->method('getCustomFields')
            ->willReturn([]);

        $this->customItemModel->expects($this->never())
            ->method('save');

        $this->expectException(NotFoundException::class);

        $this->customItemImportModel->import($this->import, self::ROW_DATA, $this->customObject);
    }

    public function testImportForUpdatedWithSetOwner(): void
    {
        $mappedFields       = self::MAPPED_FIELDS;
        $rowData            = self::ROW_DATA;
        $mappedFields['id'] = 'customItemId';
        $rowData['id']      = '555';
        $customItem         = $this->createMock(CustomItem::class);

        $customItem->expects($this->exactly(2))
            ->method('findCustomFieldValueForFieldId')
            ->willReturn($this->createMock(CustomFieldValueInterface::class));

        $customItem->expects($this->once())
            ->method('getId')
            ->willReturn(555);

        $this->import->expects($this->exactly(2))
            ->method('getMatchedFields')
            ->willReturn($mappedFields);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->with(555)
            ->willReturn($customItem);

        $this->customItemModel->expects($this->once())
            ->method('populateCustomFields')
            ->with($customItem)
            ->willReturn($customItem);

        $this->import->expects($this->exactly(1))
            ->method('getDefault')
            ->with('owner')
            ->willReturn(222);

        $this->entityManager->expects($this->exactly(1))
            ->method('find')
            ->with(User::class, 222)
            ->willReturn(222);

        $this->formatterHelper->expects($this->once())
            ->method('simpleCsvToArray')
            ->with('3262739,3262738,3262737')
            ->willReturn([3262739, 3262738, 3262737]);

        $this->customItemModel->expects($this->exactly(3))
            ->method('linkEntity')
            ->withConsecutive(
                [$customItem, 'contact', 3262739],
                [$customItem, 'contact', 3262738],
                [$customItem, 'contact', 3262737]
            );

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($customItem)
            ->willReturn($customItem);

        $this->customItemImportModel->import($this->import, $rowData, $this->customObject);
    }

    public function testImportForUpdatedWhenItemNotFound(): void
    {
        $mappedFields       = self::MAPPED_FIELDS;
        $rowData            = self::ROW_DATA;
        $mappedFields['id'] = 'customItemId';
        $rowData['id']      = '555';
        $customItem         = $this->createMock(CustomItem::class);

        $this->import->expects($this->exactly(2))
            ->method('getMatchedFields')
            ->willReturn($mappedFields);

        $this->customObject->expects($this->exactly(3))
            ->method('getCustomFields')
            ->willReturn(new ArrayCollection([$this->descriptionField, $this->dateField]));

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->with(555)
            ->will($this->throwException(new NotFoundException()));

        $this->formatterHelper->expects($this->once())
            ->method('simpleCsvToArray')
            ->with('3262739,3262738,3262737')
            ->willReturn([3262739, 3262738, 3262737]);

        $this->customItemModel->expects($this->exactly(3))
            ->method('linkEntity')
            ->withConsecutive(
                [$customItem, 'contact', 3262739],
                [$customItem, 'contact', 3262738],
                [$customItem, 'contact', 3262737]
            );

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(CustomItem::class))
            ->willReturn($customItem);

        $this->customItemImportModel->import($this->import, $rowData, $this->customObject);
    }
}
