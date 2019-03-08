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

use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Mautic\LeadBundle\Entity\Lead;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Statement;


class CustomItemRepositoryTest extends \PHPUnit_Framework_TestCase
{
    private $entityManager;
    private $classMetadata;
    private $customObject;
    private $customField;
    private $contact;
    private $queryBuilder;
    private $queryBuilderDbal;
    private $connection;
    private $statement;
    private $expr;
    private $expressionBuilder;
    private $query;
    private $customItemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager        = $this->createMock(EntityManager::class);
        $this->classMetadata        = $this->createMock(ClassMetadata::class);
        $this->customObject         = $this->createMock(CustomObject::class);
        $this->customField          = $this->createMock(CustomField::class);
        $this->contact              = $this->createMock(Lead::class);
        $this->queryBuilder         = $this->createMock(QueryBuilder::class);
        $this->queryBuilderDbal     = $this->createMock(DbalQueryBuilder::class);
        $this->connection           = $this->createMock(Connection::class);
        $this->statement            = $this->createMock(Statement::class);
        $this->expr                 = $this->createMock(Expr::class);
        $this->expressionBuilder    = $this->createMock(ExpressionBuilder::class);
        $this->query                = $this->createMock(AbstractQuery::class);
        $this->customItemRepository = new CustomItemRepository(
            $this->entityManager,
            $this->classMetadata
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilderDbal);
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($this->expr);
        $this->queryBuilderDbal->method('expr')->willReturn($this->expressionBuilder);
        $this->queryBuilderDbal->method('execute')->willReturn($this->statement);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }

    public function testCountItemsLinkedToContact(): void
    {
        $count          = 33;
        $customObjectId = 33;
        $contactId      = 33;
    
        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn($customObjectId);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($contactId);

        $this->expr->expects($this->once())
            ->method('countDistinct')
            ->with('ci.id')
            ->willReturn('COUNT(ci.id)');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(['ci'], ['COUNT(ci.id)']);

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(null, 'ci', 'ci.id');

        $this->queryBuilder->expects($this->once())
            ->method('innerJoin')
            ->with('ci.contactReferences', 'cixctct');

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('ci.customObject = :customObjectId');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customObjectId', $customObjectId],
                ['contactId', $contactId]
            );

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $this->assertSame(
            $count,
            $this->customItemRepository->countItemsLinkedToContact(
                $this->customObject,
                $this->contact
            )
        );
    }

    public function testFindItemIdForValue(): void
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $count          = 33;
        $expr           = 'lte';
        $value          = 1000;
        $contactId      = 33;
        $customFieldId  = 33;

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($contactId);

        $this->customField->expects($this->once())
            ->method('getId')
            ->willReturn($customFieldId);

        $this->customField->expects($this->once())
            ->method('getTypeObject')
            ->willReturn(new IntType('number'));

        $this->queryBuilderDbal->expects($this->once())
            ->method('select')
            ->with('ci.id');

        $this->queryBuilderDbal->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'custom_item', 'ci');

        $this->queryBuilderDbal->expects($this->exactly(2))
            ->method('innerJoin')
            ->withConsecutive(
                ['ci', MAUTIC_TABLE_PREFIX.'custom_item_xref_contact', 'cixcont', 'cixcont.custom_item_id = ci.id'],
                ['ci', 'custom_field_value_int', 'cfv_int', 'cfv_int.custom_item_id = ci.id']
            );

        $this->queryBuilderDbal->expects($this->once())
            ->method('where')
            ->with('cixcont.contact_id = :contactId');

        $this->expressionBuilder->expects($this->once())
            ->method($expr)
            ->with('cfv_int.value', ':value')
            ->willReturn('lte-expression-here');

        $this->queryBuilderDbal->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['cfv_int.custom_field_id = :customFieldId'],
                ['lte-expression-here']
            );

        $this->queryBuilderDbal->expects($this->exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['contactId', $contactId],
                ['customFieldId', $customFieldId],
                ['value', $value]
            );

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($count);

        $this->assertSame(
            $count,
            $this->customItemRepository->findItemIdForValue(
                $this->customField,
                $this->contact,
                $expr,
                $value
            )
        );
    }
}
