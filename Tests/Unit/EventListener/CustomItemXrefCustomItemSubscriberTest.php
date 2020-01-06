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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\EventListener\CustomItemXrefCustomItemSubscriber;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NoResultException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use Doctrine\ORM\Query\Expr\Join;

class CustomItemXrefCustomItemSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM_A_ID = 90;

    private const ITEM_B_ID = 123;

    private $entityManager;

    private $queryBuilder;

    private $expr;

    private $query;

    private $event;

    private $listEvent;

    private $discoveryEvent;

    private $customItemA;

    private $customItemB;

    private $xref;

    /**
     * @var CustomItemXrefCustomItemSubscriber
     */
    private $xrefSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager  = $this->createMock(EntityManager::class);
        $this->queryBuilder   = $this->createMock(QueryBuilder::class);
        $this->query          = $this->createMock(AbstractQuery::class);
        $this->expr           = $this->createMock(Expr::class);
        $this->event          = $this->createMock(CustomItemXrefEntityEvent::class);
        $this->listEvent      = $this->createMock(CustomItemListQueryEvent::class);
        $this->discoveryEvent = $this->createMock(CustomItemXrefEntityDiscoveryEvent::class);
        $this->customItemA    = $this->createMock(CustomItem::class);
        $this->customItemB    = $this->createMock(CustomItem::class);
        $this->xref           = $this->createMock(CustomItemXrefCustomItem::class);
        $this->xrefSubscriber = new CustomItemXrefCustomItemSubscriber(
            $this->entityManager
        );

        $this->event->method('getXref')->willReturn($this->xref);
        $this->xref->method('getCustomItemLower')->willReturn($this->customItemA);
        $this->xref->method('getCustomItemHigher')->willReturn($this->customItemB);
        $this->customItemA->method('getId')->willReturn(self::ITEM_A_ID);
        $this->customItemB->method('getId')->willReturn(self::ITEM_B_ID);
        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('expr')->willReturn($this->expr);
    }

    public function testOnListQueryWhenNoEntity(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->never())
            ->method('getQueryBuilder')
            ->willReturn($tableConfig);

        $this->xrefSubscriber->onListQuery($this->listEvent);
    }

    public function testOnListQuery(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');
        $tableConfig->addParameter('filterEntityType', 'customItem');
        $tableConfig->addParameter('filterEntityId', self::ITEM_B_ID);

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with(
                CustomItemXrefCustomItem::class,
                'CustomItemXrefCustomItem',
                Join::WITH,
                'CustomItem.id = CustomItemXrefCustomItem.customItemLower OR CustomItem.id = CustomItemXrefCustomItem.customItemHigher'
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['CustomItem.id != :customItemId'],
                [null] // Whatever Expr returns, don't care really.
            );

        $this->expr->expects($this->once())->method('orX');

        $this->expr->expects($this->exactly(2))
            ->method('eq')
            ->withConsecutive(
                ['CustomItemXrefCustomItem.customItemLower', ':customItemId'],
                ['CustomItemXrefCustomItem.customItemHigher', ':customItemId']
            );

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('customItemId', self::ITEM_B_ID);

        $this->xrefSubscriber->onListQuery($this->listEvent);
    }

    public function testOnLookupQueryyWhenNoEntity(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->never())
            ->method('getQueryBuilder')
            ->willReturn($tableConfig);

        $this->xrefSubscriber->onLookupQuery($this->listEvent);
    }

    public function testOnLookupQuery(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');
        $tableConfig->addParameter('filterEntityType', 'customItem');
        $tableConfig->addParameter('filterEntityId', self::ITEM_B_ID);

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with(
                CustomItemXrefCustomItem::class,
                'CustomItemXrefCustomItem',
                Join::WITH,
                'CustomItem.id = CustomItemXrefCustomItem.customItemLower OR CustomItem.id = CustomItemXrefCustomItem.customItemHigher'
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                ['CustomItem.id != :customItemId'],
                [null] // Whatever Expr returns, don't care really.
            );

        $this->expr->expects($this->once())->method('orX');

        $this->expr->expects($this->exactly(2))
            ->method('neq')
            ->withConsecutive(
                ['CustomItemXrefCustomItem.customItemLower', ':customItemId'],
                ['CustomItemXrefCustomItem.customItemHigher', ':customItemId']
            );

        $this->expr->expects($this->exactly(2))
            ->method('isNull')
            ->withConsecutive(
                ['CustomItemXrefCustomItem.customItemLower'],
                ['CustomItemXrefCustomItem.customItemHigher']
            );

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('customItemId', self::ITEM_B_ID);

        $this->xrefSubscriber->onLookupQuery($this->listEvent);
    }

    public function testOnEntityLinkDiscoveryForAnotherEntity(): void
    {
        $this->discoveryEvent->expects($this->once())
            ->method('getEntityType')
            ->willReturn('unicorn');

        $this->discoveryEvent->expects($this->never())
            ->method('setXrefEntity');

        $this->xrefSubscriber->onEntityLinkDiscovery($this->discoveryEvent);
    }

    public function testOnEntityLinkDiscoveryWhenXrefExists(): void
    {
        $this->discoveryEvent->expects($this->once())
            ->method('getEntityType')
            ->willReturn('customItem');

        $this->discoveryEvent->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::ITEM_B_ID);

        $this->discoveryEvent->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItemA);

        $this->customItemA->expects($this->once())
            ->method('getId')
            ->willReturn(self::ITEM_A_ID);

        $this->assertGetXrefEntity();

        $this->discoveryEvent->expects($this->once())
            ->method('stopPropagation');

        $this->discoveryEvent->expects($this->once())
            ->method('setXrefEntity')
            ->with($this->xref);

        $this->xrefSubscriber->onEntityLinkDiscovery($this->discoveryEvent);
    }

    public function testOnEntityLinkDiscoveryWhenXrefNotFound(): void
    {
        $this->discoveryEvent->expects($this->once())
            ->method('getEntityType')
            ->willReturn('customItem');

        $this->discoveryEvent->expects($this->exactly(2))
            ->method('getEntityId')
            ->willReturn(self::ITEM_B_ID);

        $this->discoveryEvent->expects($this->exactly(2))
            ->method('getCustomItem')
            ->willReturn($this->customItemA);

        $this->customItemA->expects($this->any())
            ->method('getId')
            ->willReturn(self::ITEM_A_ID);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->will($this->throwException(new NoResultException()));

        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(CustomItem::class, self::ITEM_B_ID)
            ->willReturn($this->customItemB);

        $this->discoveryEvent->expects($this->once())
            ->method('stopPropagation');

        $this->discoveryEvent->expects($this->once())
            ->method('setXrefEntity')
            ->with($this->callback(function (CustomItemXrefCustomItem $xref) {
                // newly created Xref entity.
                $this->assertSame($this->customItemA, $xref->getCustomItemLower());
                $this->assertSame($this->customItemB, $xref->getCustomItemHigher());

                return true;
            }));

        $this->xrefSubscriber->onEntityLinkDiscovery($this->discoveryEvent);
    }

    public function testSaveLink(): void
    {
        $this->event->expects($this->exactly(4))
            ->method('getXref')
            ->willReturn($this->xref);

        $this->entityManager->expects($this->once())
            ->method('contains')
            ->with($this->xref)
            ->willReturn(false);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->xref);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->xrefSubscriber->saveLink($this->event);
    }

    public function testCreateNewEvenLogForLinkedCustomItem(): void
    {
        // @todo once the method is implemented.

        $this->xrefSubscriber->createNewEvenLogForLinkedCustomItem($this->event);
    }

    public function testDeleteLink(): void
    {
        $this->event->expects($this->exactly(4))
            ->method('getXref')
            ->willReturn($this->xref);

        $this->entityManager->expects($this->once())
            ->method('contains')
            ->with($this->xref)
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($this->xref);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->xrefSubscriber->deleteLink($this->event);
    }

    public function testCreateNewEvenLogForUnlinkedCustomItem(): void
    {
        // @todo once the method is implemented.

        $this->xrefSubscriber->createNewEvenLogForUnlinkedCustomItem($this->event);
    }

    /**
     * Tests CustomItemXrefContactSubscriber::getXrefEntity.
     */
    private function assertGetXrefEntity(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('cixci');

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(CustomItemXrefCustomItem::class, 'cixci');

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('cixci.customItemLower = :customItemLower');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('cixci.customItemHigher = :customItemHigher');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customItemLower', self::ITEM_A_ID, null],
                ['customItemHigher', self::ITEM_B_ID, null]
            );

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn($this->xref);
    }
}
