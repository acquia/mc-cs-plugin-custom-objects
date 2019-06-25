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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use Doctrine\ORM\AbstractQuery;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CustomFieldValueModelTest extends \PHPUnit_Framework_TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->customObject          = $this->createMock(CustomObject::class);
        $this->customItem            = $this->createMock(CustomItem::class);
        $this->customField           = $this->createMock(CustomField::class);
        $this->entityManager         = $this->createMock(EntityManager::class);
        $this->connection            = $this->createMock(Connection::class);
        $this->queryBuilder          = $this->createMock(QueryBuilder::class);
        $this->statement             = $this->createMock(Statement::class);
        $this->validator             = $this->createMock(ValidatorInterface::class);
        $this->violationList         = $this->createMock(ConstraintViolationListInterface::class);
        $this->customFieldValueModel = new CustomFieldValueModel(
            $this->entityManager,
            $this->validator
        );
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
            ->willReturn(new TextType($this->createMock(TranslatorInterface::class)));

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
            ->willReturn(new TextType($this->createMock(TranslatorInterface::class)));

        $noValueField->expects($this->exactly(2))
            ->method('getTypeObject')
            ->willReturn(new IntType($this->createMock(TranslatorInterface::class)));

        $noValueField->expects($this->any())
            ->method('getId')
            ->willReturn(66);

        $noValueField->expects($this->once())
            ->method('getDefaultValue')
            ->willReturn(1000);

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
                ["cfv_text.custom_field_id, cfv_text.value, 'text' AS type"],
                ["cfv_int.custom_field_id, cfv_int.value, 'int' AS type"]
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('from')
            ->withConsecutive(
                ['custom_field_value_text'],
                ['custom_field_value_int']
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
            ->with('THE TEXT FIELD SQL QUERY UNION THE NUMBER FIELD SQL QUERY')
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

        $this->assertSame(1000, $newValue->getValue());
        $this->assertSame($noValueField, $newValue->getCustomField());
        $this->assertSame($customItem, $newValue->getCustomItem());
    }

    public function testSaveForNewCustomItem(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueInterface::class);

        $customFieldValue->expects($this->once())
            ->method('getCustomField')
            ->willReturn($this->customField);

        $customFieldValue->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
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

        $customFieldValue->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
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

        $customFieldValue->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $customFieldValue->expects($this->once())
            ->method('getValue')
            ->willReturn(['red', 'green']);

        $customFieldValue->expects($this->exactly(2))
            ->method('setValue')
            ->withConsecutive(['red'], ['green']);

        $this->customItem->expects($this->once())
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

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($customFieldValue);

        $this->validator->expects($this->exactly(2))
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

        $customFieldValue->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $customFieldValue->expects($this->once())
            ->method('getValue')
            ->willReturn('');

        $customFieldValue->expects($this->never())
            ->method('setValue');

        $this->customItem->expects($this->once())
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
