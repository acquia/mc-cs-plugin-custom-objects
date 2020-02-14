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
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;

class CustomItemRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private $entityManager;
    private $classMetadata;
    private $customObject;
    private $queryBuilder;
    private $connection;
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

        $this->entityManager        = $this->createMock(EntityManager::class);
        $this->classMetadata        = $this->createMock(ClassMetadata::class);
        $this->customObject         = $this->createMock(CustomObject::class);
        $this->contact              = $this->createMock(Lead::class);
        $this->queryBuilder         = $this->createMock(QueryBuilder::class);
        $this->connection           = $this->createMock(Connection::class);
        $this->expr                 = $this->createMock(Expr::class);
        $this->query                = $this->createMock(AbstractQuery::class);
        $this->customItemRepository = new CustomItemRepository(
            $this->entityManager,
            $this->classMetadata
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getConnection')->willReturn($this->connection);
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
            ->with(
                CustomItemXrefCustomItem::class,
                'cixci',
                Join::WITH,
                'ci.id = cixci.customItemLower OR ci.id = cixci.customItemHigher'
            );

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('ci.customObject = :customObjectId');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['ci.id != :customItemId'],
                [null] // Whatever Expr returns.
            );

        $this->expr->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['cixci.customItemLower', ':customItemId'],
                ['cixci.customItemHigher', ':customItemId']
            );

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
