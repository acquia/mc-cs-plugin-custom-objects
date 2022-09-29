<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnexpectedValueException;

class CustomItemModelTest extends TestCase
{
    private $customItem;

    private $user;

    private $entityManager;

    private $queryBuilder;

    private $dbalQueryBuilder;

    private $statement;

    private $connection;

    private $query;

    private $customItemRepository;

    private $customItemPermissionProvider;

    private $userHelper;

    private $customFieldValueModel;

    private $dispatcher;

    private $validator;

    private $violationList;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $this->customItem                   = $this->createMock(CustomItem::class);
        $this->user                         = $this->createMock(User::class);
        $this->entityManager                = $this->createMock(EntityManager::class);
        $this->queryBuilder                 = $this->createMock(QueryBuilder::class);
        $this->dbalQueryBuilder             = $this->createMock(DbalQueryBuilder::class);
        $this->statement                    = $this->createMock(Statement::class);
        $this->connection                   = $this->createMock(Connection::class);
        $this->query                        = $this->createMock(AbstractQuery::class);
        $this->customItemRepository         = $this->createMock(CustomItemRepository::class);
        $this->customItemPermissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->userHelper                   = $this->createMock(UserHelper::class);
        $this->customFieldValueModel        = $this->createMock(CustomFieldValueModel::class);
        $this->dispatcher                   = $this->createMock(EventDispatcherInterface::class);
        $this->validator                    = $this->createMock(ValidatorInterface::class);
        $this->violationList                = $this->createMock(ConstraintViolationListInterface::class);
        $this->customItemModel              = new CustomItemModel(
            $this->entityManager,
            $this->customItemRepository,
            $this->customItemPermissionProvider,
            $this->userHelper,
            $this->customFieldValueModel,
            $this->dispatcher,
            $this->validator
        );

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $this->connection->method('createQueryBuilder')->willReturn($this->dbalQueryBuilder);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->userHelper->method('getUser')->willReturn($this->user);
        $this->customItem->method('getId')->willReturn(1);
    }

    public function testSaveNew(): void
    {
        $this->user->expects($this->exactly(2))->method('getName')->willReturn('John Doe');
        $this->userHelper->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->customItem->expects($this->exactly(3))->method('isNew')->willReturn(true);
        $this->customItem->expects($this->once())->method('setCreatedBy')->with($this->user);
        $this->customItem->expects($this->once())->method('setCreatedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateAdded');
        $this->customItem->expects($this->once())->method('setModifiedBy')->with($this->user);
        $this->customItem->expects($this->once())->method('setModifiedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateModified');
        $this->customItem->expects($this->once())->method('updateUniqueHash');
        $this->customItem->expects($this->once())->method('getCustomFieldValues')->willReturn(new ArrayCollection());
        $this->customItem->expects($this->never())->method('recordCustomFieldValueChanges');
        $this->dispatcher->method('dispatch')
            ->withConsecutive(
                [CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $this->isInstanceOf(CustomItemEvent::class)],
                [CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $this->isInstanceOf(CustomItemEvent::class)]
            );
        $this->customItemRepository->expects($this->once())->method('upsert')->with($this->customItem);
        $this->validator->expects($this->once())->method('validate')->with($this->customItem)->willReturn($this->violationList);

        $this->expectException(NotFoundException::class); //since the fetchEntity() method is called on save and there's no customItem in the DB with ID 1
        $this->assertSame($this->customItem, $this->customItemModel->save($this->customItem));
    }

    public function testSaveEdit(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueText::class);
        $this->user->expects($this->once())->method('getName')->willReturn('John Doe');
        $this->userHelper->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->customItem->expects($this->exactly(4))->method('isNew')->willReturn(false);
        $this->customItem->expects($this->once())->method('setModifiedBy')->with($this->user);
        $this->customItem->expects($this->once())->method('setModifiedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateModified');
        $this->customItem->expects($this->once())->method('getCustomFieldValues')->willReturn(new ArrayCollection([$customFieldValue]));
        $this->customItem->expects($this->once())->method('recordCustomFieldValueChanges');
        $this->customFieldValueModel->expects($this->once())->method('save')->with($customFieldValue);
        $this->dispatcher->method('dispatch')
            ->withConsecutive(
                [CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $this->isInstanceOf(CustomItemEvent::class)],
                [CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $this->isInstanceOf(CustomItemEvent::class)]
            );
        $this->validator->expects($this->once())->method('validate')->with($this->customItem)->willReturn($this->violationList);

        $this->assertSame($this->customItem, $this->customItemModel->save($this->customItem));
    }

    public function testDelete(): void
    {
        $this->customItem->expects($this->once())->method('getId')->willReturn(34);
        $this->dispatcher->method('dispatch')
            ->withConsecutive(
                [CustomItemEvents::ON_CUSTOM_ITEM_PRE_DELETE, $this->isInstanceOf(CustomItemEvent::class)],
                [CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE, $this->isInstanceOf(CustomItemEvent::class)]
            );
        $this->entityManager->expects($this->once())->method('remove')->with($this->customItem);
        $this->entityManager->expects($this->once())->method('flush');

        $this->customItemModel->delete($this->customItem);
    }

    public function testLinkEntityIfXrefNotFound(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY,
                $this->callback(function (CustomItemXrefEntityDiscoveryEvent $event) {
                    $this->assertSame($this->customItem, $event->getCustomItem());
                    $this->assertSame('contact', $event->getEntityType());
                    $this->assertSame(123, $event->getEntityId());

                    return true;
                })
            );

        $this->expectException(UnexpectedValueException::class);
        $this->customItemModel->linkEntity($this->customItem, 'contact', 123);
    }

    public function testLinkEntity(): void
    {
        $xref = $this->createMock(CustomItemXrefInterface::class);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY,
                    $this->callback(function (CustomItemXrefEntityDiscoveryEvent $event) use ($xref) {
                        $this->assertSame($this->customItem, $event->getCustomItem());
                        $this->assertSame('contact', $event->getEntityType());
                        $this->assertSame(123, $event->getEntityId());

                        // Simulate that a subscriber subscribed a Xref entity.
                        $event->setXrefEntity($xref);

                        return true;
                    }),
                ],
                [
                    CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY,
                    $this->callback(function (CustomItemXrefEntityEvent $event) use ($xref) {
                        $this->assertSame($xref, $event->getXref());

                        return true;
                    }),
                ]
            );

        $this->assertSame($xref, $this->customItemModel->linkEntity($this->customItem, 'contact', 123));
    }

    public function testUninkEntityIfXrefNotFound(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY,
                $this->callback(function (CustomItemXrefEntityDiscoveryEvent $event) {
                    $this->assertSame($this->customItem, $event->getCustomItem());
                    $this->assertSame('contact', $event->getEntityType());
                    $this->assertSame(123, $event->getEntityId());

                    return true;
                })
            );

        $this->expectException(UnexpectedValueException::class);
        $this->customItemModel->unlinkEntity($this->customItem, 'contact', 123);
    }

    public function testUninkEntity(): void
    {
        $xref = $this->createMock(CustomItemXrefInterface::class);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY,
                    $this->callback(function (CustomItemXrefEntityDiscoveryEvent $event) use ($xref) {
                        $this->assertSame($this->customItem, $event->getCustomItem());
                        $this->assertSame('contact', $event->getEntityType());
                        $this->assertSame(123, $event->getEntityId());

                        // Simulate that a subscriber subscribed a Xref entity.
                        $event->setXrefEntity($xref);

                        return true;
                    }),
                ],
                [
                    CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_ENTITY,
                    $this->callback(function (CustomItemXrefEntityEvent $event) use ($xref) {
                        $this->assertSame($xref, $event->getXref());

                        return true;
                    }),
                ]
            );

        $this->assertSame($xref, $this->customItemModel->unlinkEntity($this->customItem, 'contact', 123));
    }

    public function testFetchEntity(): void
    {
        $this->customItemRepository->expects($this->once())
            ->method('getEntity')
            ->willReturn($this->customItem);

        $this->customItem->expects($this->once())
            ->method('getCustomFieldValues')
            ->willReturn(new ArrayCollection([$this->createMock(CustomFieldValueInterface::class)]));

        $this->customItem->expects($this->never())
            ->method('getCustomObject');

        $this->customItemModel->fetchEntity(44);
    }

    public function testFetchEntityIfNotFound(): void
    {
        $this->customItemRepository->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->customItem->expects($this->never())
            ->method('getCustomFieldValues');

        $this->customItem->expects($this->never())
            ->method('getCustomObject');

        $this->expectException(NotFoundException::class);

        $this->customItemModel->fetchEntity(44);
    }

    public function testGetTableDataWithoutCustomObjectId(): void
    {
        $tableConfig = new TableConfig(10, 1, 'column');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("customObjectId cannot be empty. It's required for permission management");
        $this->customItemModel->getTableData($tableConfig);
    }

    public function testGetTableData(): void
    {
        $tableConfig = new TableConfig(10, 1, 'column');
        $tableConfig->addParameter('customObjectId', 44);

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->any())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CustomItemEvents::ON_CUSTOM_ITEM_LIST_ORM_QUERY);

        $this->customItemModel->getTableData($tableConfig);
    }

    public function testGetArrayTableDataWithoutCustomObjectId(): void
    {
        $tableConfig = new TableConfig(10, 1, 'column');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("customObjectId cannot be empty. It's required for permission management");
        $this->customItemModel->getArrayTableData($tableConfig);
    }

    public function testGetArrayTableData(): void
    {
        $tableConfig = new TableConfig(10, 1, 'column');
        $tableConfig->addParameter('customObjectId', 44);

        $this->customItemPermissionProvider->expects($this->never())
            ->method('isGranted');

        // Model situation when the method is called from a command and user is unknown.
        $this->user->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->dbalQueryBuilder->expects($this->once())
            ->method('select')
            ->willReturn(CustomItem::TABLE_ALIAS.'.*');

        $this->dbalQueryBuilder->expects($this->once())
            ->method('execute')
            ->willReturn($this->statement);

        $this->dbalQueryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('customObjectId', 44);

        $this->statement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CustomItemEvents::ON_CUSTOM_ITEM_LIST_DBAL_QUERY);

        $this->customItemModel->getArrayTableData($tableConfig);
    }

    public function testGetCountForTable(): void
    {
        $expr        = $this->createMock(Expr::class);
        $tableConfig = new TableConfig(10, 2, 'column');
        $tableConfig->addParameter('customObjectId', 44);

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->any())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomItem::TABLE_ALIAS],
                ['the select count expr']
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setMaxResults')
            ->withConsecutive([10], [1]);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setFirstResult')
            ->withConsecutive([10], [0]);

        $this->queryBuilder->expects($this->once())
            ->method('resetDQLPart')
            ->with('orderBy');

        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($expr);

        $expr->expects($this->once())
            ->method('countDistinct')
            ->with('CustomItem')
            ->willReturn('the select count expr');

        $this->queryBuilder->expects($this->once())
            ->method('from')
            ->with(CustomItem::class, CustomItem::TABLE_ALIAS);

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with(CustomItem::TABLE_ALIAS.'.customObject = :customObjectId');

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with(CustomItem::TABLE_ALIAS.'.createdBy', 22);

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('customObjectId', 44);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(4);

        $this->assertSame(4, $this->customItemModel->getCountForTable($tableConfig));
    }

    public function testGetLookupData(): void
    {
        $tableConfig = new TableConfig(10, 1, 'column');
        $tableConfig->addParameter('customObjectId', 44);
        $tableConfig->addParameter('search', 'Item A');

        $this->queryBuilder->method('expr')->willReturn(new Expr());

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->any())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->exactly(4))
            ->method('select')
            ->withConsecutive(
                [CustomItem::TABLE_ALIAS],
                ['IDENTITY(ValueText.customItem)'],
                ['IDENTITY(ValueOption.customItem)'],
                ['CustomItem.name as value, CustomItem.id']
            );

        $this->queryBuilder->expects($this->any())
            ->method('andWhere')
            ->withConsecutive(
                ['MATCH (ValueText.value) AGAINST (:search BOOLEAN) > 0'],
                ['MATCH (ValueOption.value) AGAINST (:search BOOLEAN) > 0']
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customObjectId', 44],
                ['search', '(+Item* +A*) >"Item A"']
            );

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([123 => ['id' => 123, 'value' => 'Test Item']]);

        $this->assertSame(
            [['id' => 123, 'value' => 'Test Item (123)']],
            $this->customItemModel->getLookupData($tableConfig)
        );
    }

    public function testPopulateCustomFields(): void
    {
        $this->customItem->expects($this->once())
            ->method('getCustomFieldValues')
            ->willReturn(new ArrayCollection());

        $this->customFieldValueModel->expects($this->once())
            ->method('createValuesForItem')
            ->with($this->customItem);

        $this->customItem->expects($this->once())
            ->method('createFieldValuesSnapshot');

        $this->assertSame(
            $this->customItem,
            $this->customItemModel->populateCustomFields($this->customItem)
        );
    }

    public function testGetPermissionBase(): void
    {
        $this->assertSame('custom_objects:custom_objects', $this->customItemModel->getPermissionBase());
    }
}
