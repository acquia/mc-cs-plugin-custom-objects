<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomFieldValueModelTest extends \PHPUnit\Framework\TestCase
{
    private $customObject;
    private $customItem;
    private $customField;
    private $entityManager;
    private $connection;
    private $queryBuilder;
    private $statement;
    private $customFieldValueModel;
    private $validator;
    private $violationList;
    private $filterOperatorProvider;
    private $translator;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->customObject           = $this->createMock(CustomObject::class);
        $this->customItem             = $this->createMock(CustomItem::class);
        $this->customField            = $this->createMock(CustomField::class);
        $this->entityManager          = $this->createMock(EntityManager::class);
        $this->connection             = $this->createMock(Connection::class);
        $this->queryBuilder           = $this->createMock(QueryBuilder::class);
        $this->statement              = $this->createMock(Statement::class);
        $this->validator              = $this->createMock(ValidatorInterface::class);
        $this->violationList          = $this->createMock(ConstraintViolationListInterface::class);
        $this->filterOperatorProvider = $this->createMock(FilterOperatorProviderInterface::class);
        $this->translator             = $this->createMock(TranslatorInterface::class);
        $this->customFieldValueModel  = new CustomFieldValueModel(
            $this->entityManager,
            $this->validator
        );

        $this->entityManager->method('getReference')->willReturn($this->customItem);
    }

    public function testGetValuesForItemIfItemDoesNotHaveId(): void
    {
        $customFields = new ArrayCollection([$this->customField]);

        $this->customItem->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customObject->expects($this->once())
            ->method('getPublishedFields')
            ->willReturn($customFields);

        $this->customItem->expects($this->once())
            ->method('isNew')
            ->willReturn(true);

        $this->customField->expects($this->once())
            ->method('getTypeObject')
            ->willReturn(new TextType($this->translator, $this->filterOperatorProvider));

        $this->entityManager->expects($this->never())
            ->method('getConnection');

        $this->customFieldValueModel->createValuesForItem($this->customItem);
    }

    public function testGetValuesForItemIfItemHasId(): void
    {
        $noValueField = $this->createMock(CustomField::class);
        $customFields = new ArrayCollection([44 => $this->customField, 66 => $noValueField]);
        $customItem   = $this->getMockBuilder(CustomItem::class)
            ->setMethods(['getCustomObject', 'getPublishedFields', 'getId', 'isNew'])
            ->disableOriginalConstructor()
            ->getMock();

        $customItem->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customObject->expects($this->once())
            ->method('getPublishedFields')
            ->willReturn($customFields);

        $customItem->expects($this->any())
            ->method('getId')
            ->willReturn(33);

        $customItem->expects($this->once())
            ->method('isNew')
            ->willReturn(false);

        $this->customField->expects($this->exactly(2))
            ->method('getTypeObject')
            ->willReturn(new TextType($this->translator, $this->filterOperatorProvider));

        $noValueField->expects($this->exactly(2))
            ->method('getTypeObject')
            ->willReturn(new IntType($this->translator, $this->filterOperatorProvider));

        $noValueField->expects($this->any())
            ->method('getId')
            ->willReturn(66);

        $this->customField->expects($this->any())
            ->method('getId')
            ->willReturn(44);

        $this->entityManager->expects($this->exactly(3))
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->connection->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                ["cfv_text.custom_field_id, cfv_text.custom_item_id, cfv_text.value, 'text' AS type"],
                ["cfv_int.custom_field_id, cfv_int.custom_item_id, cfv_int.value, 'int' AS type"]
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('from')
            ->withConsecutive(
                [MAUTIC_TABLE_PREFIX.'custom_field_value_text'],
                [MAUTIC_TABLE_PREFIX.'custom_field_value_int']
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['cfv_text.custom_item_id = 33'],
                ['cfv_int.custom_item_id = 33']
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['cfv_text.custom_field_id = 44'],
                ['cfv_int.custom_field_id = 66']
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('getSQL')
            ->will($this->onConsecutiveCalls(
                'THE TEXT FIELD SQL QUERY',
                'THE NUMBER FIELD SQL QUERY'
            ));

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with('THE TEXT FIELD SQL QUERY UNION ALL THE NUMBER FIELD SQL QUERY')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute');

        $this->statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([[
                'custom_field_id' => 44,
                'custom_item_id'  => 33,
                'value'           => 'Yellow Submarine',
            ]]);

        $this->customFieldValueModel->createValuesForItem($customItem);

        $values = $customItem->getCustomFieldValues();

        $this->assertSame(2, $values->count());

        $storedValue = $values->get(44);
        $newValue    = $values->get(66);

        $this->assertSame('Yellow Submarine', $storedValue->getValue());
        $this->assertSame($this->customField, $storedValue->getCustomField());
        $this->assertSame($customItem, $storedValue->getCustomItem());

        $this->assertSame(0, $newValue->getValue());
        $this->assertSame($noValueField, $newValue->getCustomField());
        $this->assertSame($customItem, $newValue->getCustomItem());
    }

    public function testSaveForNewCustomItem(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueInterface::class);

        $customFieldValue->expects($this->once())
            ->method('getCustomField')
            ->willReturn($this->customField);

        $customFieldValue->expects($this->exactly(2))
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(99);

        $this->entityManager->expects($this->once())
            ->method('merge')
            ->with($customFieldValue);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($customFieldValue)
            ->willReturn($this->violationList);

        $this->customFieldValueModel->save($customFieldValue);
    }

    public function testSaveForExistingCustomItem(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueInterface::class);

        $customFieldValue->expects($this->once())
            ->method('getCustomField')
            ->willReturn($this->customField);

        $customFieldValue->expects($this->exactly(2))
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($customFieldValue);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($customFieldValue)
            ->willReturn($this->violationList);

        $this->customFieldValueModel->save($customFieldValue);
    }

    public function testSaveForMultivalueField(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueInterface::class);
        $query            = $this->createMock(AbstractQuery::class);

        $customFieldValue->expects($this->exactly(2))
            ->method('getCustomField')
            ->willReturn($this->customField);

        $customFieldValue->expects($this->exactly(2))
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $customFieldValue->expects($this->once())
            ->method('getValue')
            ->willReturn(['red', 'green', 4]);

        $customFieldValue->expects($this->exactly(3))
            ->method('setValue')
            ->withConsecutive(['red'], ['green'], [4]);

        $this->customItem->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(99);

        $this->customField->expects($this->any())
            ->method('getId')
            ->willReturn(44);

        $this->customField->expects($this->once())
            ->method('canHaveMultipleValues')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->with('
            delete from MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption cfvo  
            where cfvo.customField = 44
            and cfvo.customItem = 99
        ')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->willReturn(2);

        $this->entityManager->expects($this->exactly(3))
            ->method('persist')
            ->with($customFieldValue);

        $this->validator->expects($this->exactly(3))
            ->method('validate')
            ->with($customFieldValue)
            ->willReturn($this->violationList);

        $this->customFieldValueModel->save($customFieldValue);
    }

    public function testSaveForMultivalueFieldWithNoOptionsProvided(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueInterface::class);
        $query            = $this->createMock(AbstractQuery::class);

        $customFieldValue->expects($this->exactly(2))
            ->method('getCustomField')
            ->willReturn($this->customField);

        $customFieldValue->expects($this->exactly(2))
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $customFieldValue->expects($this->once())
            ->method('getValue')
            ->willReturn('');

        $customFieldValue->expects($this->never())
            ->method('setValue');

        $this->customItem->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(99);

        $this->customField->expects($this->any())
            ->method('getId')
            ->willReturn(44);

        $this->customField->expects($this->once())
            ->method('canHaveMultipleValues')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->with('
            delete from MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption cfvo  
            where cfvo.customField = 44
            and cfvo.customItem = 99
        ')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->willReturn(2);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->validator->expects($this->never())
            ->method('validate');

        $this->customFieldValueModel->save($customFieldValue);
    }
}
