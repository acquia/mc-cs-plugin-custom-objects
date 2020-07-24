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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use PHPUnit\Framework\TestCase;

class CustomItemRepositoryTest extends TestCase
{
    private $customObject;
    private $queryBuilder;
    private $contact;
    private $expr;
    private $query;

    /**
     * @var CustomItemRepository
     */
    private $customItemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $entityManager              = $this->createMock(EntityManager::class);
        $classMetadata              = $this->createMock(ClassMetadata::class);
        $connection                 = $this->createMock(Connection::class);
        $this->customObject         = $this->createMock(CustomObject::class);
        $this->contact              = $this->createMock(Lead::class);
        $this->queryBuilder         = $this->createMock(QueryBuilder::class);
        $this->expr                 = $this->createMock(Expr::class);
        $this->query                = $this->createMock(AbstractQuery::class);
        $this->customItemRepository = new CustomItemRepository(
            $entityManager,
            $classMetadata
        );

        $entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $entityManager->method('getConnection')->willReturn($connection);
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('expr')->willReturn($this->expr);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }

    public function testCountItemsLinkedToContact(): void
    {
        $count          = 33;
        $customObjectId = 43;
        $contactId      = 53;

        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn($customObjectId);

        $this->contact->expects($this->once())
            ->method('getId')
            ->willReturn($contactId);

        $this->expr->expects($this->once())
            ->method('countDistinct')
            ->with('CustomItem.id')
            ->willReturn('COUNT(CustomItem.id)');

        $this->queryBuilder->expects($this->exactly(3))
            ->method('select')
            ->withConsecutive(['CustomItem'], ['COUNT(CustomItem.id)'], ['IDENTITY(contactReference.customItem)']);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('from')
            ->withConsecutive([null, 'CustomItem'], [CustomItemXrefContact::class, 'contactReference']);

        $this->queryBuilder->expects($this->any())
            ->method('where')
            ->withConsecutive(['CustomItem.customObject = :customObjectId'], ['contactReference.contact = :contactId']);

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

    public function testCountItemsLinkedToAnotherItem(): void
    {
        $count          = 33;
        $customObjectId = 43;
        $customItemId   = 53;
        $customItem     = $this->createMock(CustomItem::class);

        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn($customObjectId);

        $customItem->expects($this->once())
            ->method('getId')
            ->willReturn($customItemId);

        $this->expr->expects($this->once())
            ->method('countDistinct')
            ->with('CustomItem.id')
            ->willReturn('COUNT(CustomItem.id)');

        $this->queryBuilder->expects($this->exactly(4))
            ->method('select')
            ->withConsecutive(
                ['CustomItem'],
                ['COUNT(CustomItem.id)'],
                ['IDENTITY(lower.customItemLower)'],
                ['IDENTITY(higher.customItemHigher)']
            );

        $this->queryBuilder->expects($this->exactly(3))
            ->method('from')
            ->withConsecutive([null], [CustomItemXrefCustomItem::class], [CustomItemXrefCustomItem::class]);

        $this->queryBuilder->expects($this->exactly(3))
            ->method('where')
            ->withConsecutive(['CustomItem.customObject = :customObjectId'], ['lower.customItemHigher = :customItemId']);

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with(null);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customObjectId', $customObjectId],
                ['customItemId', $customItemId]
            );

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $this->assertSame(
            $count,
            $this->customItemRepository->countItemsLinkedToAnotherItem(
                $this->customObject,
                $customItem
            )
        );
    }

    public function testGetTableAlias(): void
    {
        $this->assertSame(CustomItem::TABLE_ALIAS, $this->customItemRepository->getTableAlias());
    }
}
