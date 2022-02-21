<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomItemXrefContactSubscriber;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomItemXrefContactSubscriberTest extends TestCase
{
    private const ITEM_ID = 90;

    private const ENTITY_ID = 123;

    private const USER_ID = 4;

    private const USER_NAME = 'Joe';

    private $entityManager;

    private $queryBuilder;

    private $queryBuilderDbal;

    private $query;

    private $event;

    private $listEvent;

    private $listDbalEvent;

    private $discoveryEvent;

    private $contact;

    private $customItem;

    private $xref;

    /**
     * @var CustomItemXrefContactSubscriber|MockObject
     */
    private $xrefSubscriber;

    /**
     * @var CustomItemRepository|MockObject
     */
    private $customItemRepository;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', '');

        $this->entityManager        = $this->createMock(EntityManager::class);
        $this->queryBuilder         = $this->createMock(QueryBuilder::class);
        $this->queryBuilderDbal     = $this->createMock(DbalQueryBuilder::class);
        $this->query                = $this->createMock(AbstractQuery::class);
        $userHelper                 = $this->createMock(UserHelper::class);
        $this->customItemRepository = $this->createMock(CustomItemRepository::class);
        $user                       = $this->createMock(User::class);
        $this->event                = $this->createMock(CustomItemXrefEntityEvent::class);
        $this->listEvent            = $this->createMock(CustomItemListQueryEvent::class);
        $this->listDbalEvent        = $this->createMock(CustomItemListDbalQueryEvent::class);
        $this->discoveryEvent       = $this->createMock(CustomItemXrefEntityDiscoveryEvent::class);
        $this->contact              = $this->createMock(Lead::class);
        $this->customItem           = $this->createMock(CustomItem::class);
        $this->xref                 = $this->createMock(CustomItemXrefContact::class);
        $this->xrefSubscriber       = new CustomItemXrefContactSubscriber(
            $this->entityManager,
            $userHelper,
            $this->customItemRepository
        );

        $this->event->method('getXref')->willReturn($this->xref);
        $this->xref->method('getContact')->willReturn($this->contact);
        $this->xref->method('getCustomItem')->willReturn($this->customItem);
        $this->customItem->method('getId')->willReturn(self::ITEM_ID);
        $userHelper->method('getUser')->willReturn($user);
        $user->method('getId')->willReturn(self::USER_ID);
        $user->method('getName')->willReturn(self::USER_NAME);
        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
    }

    public function testOnListDbalQueryWhenNoEntity(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');

        $this->listDbalEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listDbalEvent->expects($this->never())
            ->method('getQueryBuilder');

        $this->xrefSubscriber->onListDbalQuery($this->listDbalEvent);
    }

    public function testOnListDbalQuery(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', self::ENTITY_ID);

        $this->listDbalEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listDbalEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilderDbal);

        $this->queryBuilderDbal->expects($this->once())
            ->method('leftJoin')
            ->with(
                CustomItem::TABLE_ALIAS,
                MAUTIC_TABLE_PREFIX.CustomItemXrefContact::TABLE_NAME,
                CustomItemXrefContact::TABLE_ALIAS,
                CustomItem::TABLE_ALIAS.'.id = '.CustomItemXrefContact::TABLE_ALIAS.'.custom_item_id'
            );

        $this->queryBuilderDbal->expects($this->once())
            ->method('andWhere')
            ->with(CustomItemXrefContact::TABLE_ALIAS.'.contact_id = :contactId');

        $this->queryBuilderDbal->expects($this->once())
            ->method('setParameter')
            ->with('contactId', self::ENTITY_ID);

        $this->xrefSubscriber->onListDbalQuery($this->listDbalEvent);
    }

    public function testOnListQueryWhenNoEntity(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->never())
            ->method('getQueryBuilder');

        $this->xrefSubscriber->onListOrmQuery($this->listEvent);
    }

    public function testOnListQuery(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', self::ENTITY_ID);

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->customItemRepository->expects($this->once())
            ->method('includeItemsLinkedToContact')
            ->with($this->queryBuilder, self::ENTITY_ID);

        $this->xrefSubscriber->onListOrmQuery($this->listEvent);
    }

    public function testOnLookupQueryyWhenNoEntity(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->never())
            ->method('getQueryBuilder');

        $this->xrefSubscriber->onLookupQuery($this->listEvent);
    }

    public function testOnLookupQuery(): void
    {
        $tableConfig = new TableConfig(10, 1, 'id');
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', self::ENTITY_ID);

        $this->listEvent->expects($this->once())
            ->method('getTableConfig')
            ->willReturn($tableConfig);

        $this->listEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->customItemRepository->expects($this->once())
            ->method('excludeItemsLinkedToContact')
            ->with($this->queryBuilder, self::ENTITY_ID);

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
            ->willReturn('contact');

        $this->discoveryEvent->expects($this->once())
            ->method('getEntityId')
            ->willReturn(self::ENTITY_ID);

        $this->discoveryEvent->expects($this->once())
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
            ->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->assertGetContactXrefEntity();

        $this->discoveryEvent->expects($this->once())
            ->method('stopPropagation');

        $this->discoveryEvent->expects($this->once())
            ->method('setXrefEntity')
            ->with($this->xref);

        $this->xrefSubscriber->onEntityLinkDiscovery($this->discoveryEvent);
    }

    public function testOnEntityLinkDiscoveryWhenXrefNotFound(): void
    {
        $contact = new Lead();

        $this->discoveryEvent->expects($this->once())
            ->method('getEntityType')
            ->willReturn('contact');

        $this->discoveryEvent->expects($this->exactly(2))
            ->method('getEntityId')
            ->willReturn(self::ENTITY_ID);

        $this->discoveryEvent->expects($this->exactly(2))
            ->method('getCustomItem')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
            ->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->will($this->throwException(new NoResultException()));

        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(Lead::class, self::ENTITY_ID)
            ->willReturn($contact);

        $this->discoveryEvent->expects($this->once())
            ->method('stopPropagation');

        $this->discoveryEvent->expects($this->once())
            ->method('setXrefEntity')
            ->with($this->callback(function (CustomItemXrefContact $xref) use ($contact) {
                // newly created Xref entity.
                $this->assertSame($this->customItem, $xref->getCustomItem());
                $this->assertSame($contact, $xref->getContact());

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

    public function testCreateNewEventLogForLinkedContact(): void
    {
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback($this->makePersistCallback('link')));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $customObject = new CustomObject();
        $customObject->setType(CustomObject::TYPE_MASTER);

        $this->customItem->method('getCustomObject')
            ->willReturn($customObject);

        $this->xrefSubscriber->createNewEventLogForLinkedContact($this->event);
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

    public function testCreateNewEventLogForUnlinkedContact(): void
    {
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback($this->makePersistCallback('unlink')));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $customObject = new CustomObject();
        $customObject->setType(CustomObject::TYPE_MASTER);

        $this->customItem->method('getCustomObject')
            ->willReturn($customObject);

        $this->xrefSubscriber->createNewEventLogForUnlinkedContact($this->event);
    }

    private function makePersistCallback(string $action): callable
    {
        return function (LeadEventLog $eventLog) use ($action) {
            $this->assertSame(self::USER_ID, $eventLog->getUserId());
            $this->assertSame(self::USER_NAME, $eventLog->getUserName());
            $this->assertSame('CustomObject', $eventLog->getBundle());
            $this->assertSame('CustomItem', $eventLog->getObject());
            $this->assertSame(self::ITEM_ID, $eventLog->getObjectId());
            $this->assertSame($action, $eventLog->getAction());
            $this->assertSame($this->contact, $eventLog->getLead());

            return true;
        };
    }

    /**
     * Tests CustomItemXrefContactSubscriber::getContactXrefEntity.
     */
    private function assertGetContactXrefEntity(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('cixcont');

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(CustomItemXrefContact::class, 'cixcont');

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('cixcont.customItem = :customItemId');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('cixcont.contact = :contactId');

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customItemId', self::ITEM_ID, null],
                ['contactId', self::ENTITY_ID, null]
            );

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getSingleResult')
            ->willReturn($this->xref);
    }
}
