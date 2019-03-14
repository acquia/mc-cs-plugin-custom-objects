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
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use Doctrine\ORM\AbstractQuery;

class CustomFieldValueModelTest extends \PHPUnit_Framework_TestCase
{
    private $customItem;
    private $customField;
    private $entityManager;
    private $connection;
    private $queryBuilder;
    private $statement;
    private $customFieldValueModel;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->customItem            = $this->createMock(CustomItem::class);
        $this->customField           = $this->createMock(CustomField::class);
        $this->entityManager         = $this->createMock(EntityManager::class);
        $this->connection            = $this->createMock(Connection::class);
        $this->queryBuilder          = $this->createMock(QueryBuilder::class);
        $this->statement             = $this->createMock(Statement::class);
        $this->customFieldValueModel = new CustomFieldValueModel(
            $this->entityManager
        );
    }

    public function testGetValuesForItemIfItemDoesNotHaveId(): void
    {
        $customFields = new ArrayCollection([$this->customField]);

        $this->customItem->expects($this->once())
            ->method('getId');

        $this->customField->expects($this->never())
            ->method('getTypeObject');

        $this->customFieldValueModel->getValuesForItem(
            $this->customItem,
            $customFields
        );
    }

    public function testGetValuesForItemIfItemHasId(): void
    {
        $noValueField = $this->createMock(CustomField::class);
        $customFields = new ArrayCollection([$this->customField, $noValueField]);

        $this->customItem->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(33);

        $this->customField->expects($this->exactly(2))
            ->method('getTypeObject')
            ->willReturn(new TextType('Text'));

        $noValueField->expects($this->exactly(2))
            ->method('getTypeObject')
            ->willReturn(new IntType('Number'));

        $noValueField->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(66);

        $noValueField->expects($this->once())
            ->method('getDefaultValue')
            ->willReturn(1000);

        $this->customField->expects($this->exactly(2))
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

        $values = $this->customFieldValueModel->getValuesForItem(
            $this->customItem,
            $customFields
        );

        $this->assertSame(2, $values->count());

        $storedValue = $values->get(0);
        $newValue    = $values->get(1);

        $this->assertSame('Yellow Submarine', $storedValue->getValue());
        $this->assertSame($this->customField, $storedValue->getCustomField());
        $this->assertSame($this->customItem, $storedValue->getCustomItem());
        $this->assertTrue($storedValue->shouldBeUpdatedManually());

        $this->assertSame(1000, $newValue->getValue());
        $this->assertSame($noValueField, $newValue->getCustomField());
        $this->assertSame($this->customItem, $newValue->getCustomItem());
        $this->assertFalse($newValue->shouldBeUpdatedManually());
    }

    public function testSaveForEntityLoadedByEntityManager(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueInterface::class);

        $customFieldValue->expects($this->once())
            ->method('shouldBeUpdatedManually')
            ->willReturn(false);

        $customFieldValue->expects($this->never())
            ->method('getCustomField');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($customFieldValue);

        $this->customFieldValueModel->save($customFieldValue);
    }

    public function testSaveForEntityLoadedByDbalQuery(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueInterface::class);
        $queryBuilder     = $this->createMock(OrmQueryBuilder::class);
        $query            = $this->createMock(AbstractQuery::class);

        $customFieldValue->expects($this->once())
            ->method('shouldBeUpdatedManually')
            ->willReturn(true);

        $customFieldValue->expects($this->once())
            ->method('getValue')
            ->willReturn('Včela píchá');

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $customFieldValue->expects($this->exactly(2))
            ->method('getCustomField')
            ->willReturn($this->customField);

        $customFieldValue->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customField->expects($this->once())
            ->method('getTypeObject')
            ->willReturn(new TextType('Text'));

        $this->customField->expects($this->once())
            ->method('getId')
            ->willReturn(54);

        $this->customItem->expects($this->once())
            ->method('getId')
            ->willReturn(23);

        $queryBuilder->expects($this->once())
            ->method('update')
            ->with(CustomFieldValueText::class, 'cfv_text');

        $queryBuilder->expects($this->once())
            ->method('set')
            ->with('cfv_text.value', ':value');

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('cfv_text.customField = :customFieldId');

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('cfv_text.customItem = :customItemId');

        $queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['value', 'Včela píchá', null],
                ['customFieldId', 54, null],
                ['customItemId', 23, null]
            );

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute');

        $this->customFieldValueModel->save($customFieldValue);
    }
}
