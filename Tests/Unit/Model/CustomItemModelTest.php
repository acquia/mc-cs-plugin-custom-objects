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

use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Mautic\CoreBundle\Helper\UserHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Doctrine\ORM\Query\Expr;

class CustomItemModelTest extends \PHPUnit_Framework_TestCase
{
    private $customItem;

    private $user;

    private $entityManager;

    private $queryBuilder;

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

        $this->customItem                   = $this->createMock(CustomItem::class);
        $this->user                         = $this->createMock(User::class);
        $this->entityManager                = $this->createMock(EntityManager::class);
        $this->queryBuilder                 = $this->createMock(QueryBuilder::class);
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
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->userHelper->method('getUser')->willReturn($this->user);
    }

    public function testSaveNew(): void
    {
        $this->user->expects($this->exactly(2))->method('getName')->willReturn('John Doe');
        $this->userHelper->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->customItem->expects($this->exactly(2))->method('isNew')->willReturn(true);
        $this->customItem->expects($this->once())->method('setCreatedBy')->with($this->user);
        $this->customItem->expects($this->once())->method('setCreatedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateAdded');
        $this->customItem->expects($this->once())->method('setModifiedBy')->with($this->user);
        $this->customItem->expects($this->once())->method('setModifiedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateModified');
        $this->entityManager->expects($this->at(0))->method('persist')->with($this->customItem);
        $this->customItem->expects($this->once())->method('getCustomFieldValues')->willReturn(new ArrayCollection());
        $this->customItem->expects($this->once())->method('recordCustomFieldValueChanges');
        $this->dispatcher->expects($this->at(0))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $this->isInstanceOf(CustomItemEvent::class));
        $this->entityManager->expects($this->at(1))->method('flush');
        $this->dispatcher->expects($this->at(1))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $this->isInstanceOf(CustomItemEvent::class));
        $this->validator->expects($this->once())->method('validate')->with($this->customItem)->willReturn($this->violationList);

        $this->assertSame($this->customItem, $this->customItemModel->save($this->customItem));
    }

    public function testSaveEdit(): void
    {
        $customFieldValue = $this->createMock(CustomFieldValueText::class);
        $this->user->expects($this->once())->method('getName')->willReturn('John Doe');
        $this->userHelper->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->customItem->expects($this->exactly(2))->method('isNew')->willReturn(false);
        $this->customItem->expects($this->once())->method('setModifiedBy')->with($this->user);
        $this->customItem->expects($this->once())->method('setModifiedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateModified');
        $this->entityManager->expects($this->at(0))->method('persist')->with($this->customItem);
        $this->customItem->expects($this->once())->method('getCustomFieldValues')->willReturn(new ArrayCollection([$customFieldValue]));
        $this->customItem->expects($this->once())->method('recordCustomFieldValueChanges');
        $this->customFieldValueModel->expects($this->once())->method('save')->with($customFieldValue);
        $this->dispatcher->expects($this->at(0))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $this->isInstanceOf(CustomItemEvent::class));
        $this->entityManager->expects($this->at(1))->method('flush');
        $this->dispatcher->expects($this->at(1))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $this->isInstanceOf(CustomItemEvent::class));
        $this->validator->expects($this->once())->method('validate')->with($this->customItem)->willReturn($this->violationList);

        $this->assertSame($this->customItem, $this->customItemModel->save($this->customItem));
    }

    public function testDelete(): void
    {
        $this->customItem->expects($this->once())->method('getId')->willReturn(34);
        $this->dispatcher->expects($this->at(0))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_PRE_DELETE, $this->isInstanceOf(CustomItemEvent::class));
        $this->entityManager->expects($this->at(0))->method('remove')->with($this->customItem);
        $this->entityManager->expects($this->at(1))->method('flush');
        $this->dispatcher->expects($this->at(1))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE, $this->isInstanceOf(CustomItemEvent::class));

        $this->customItemModel->delete($this->customItem);
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

    public function testGetTableData(): void
    {
        $tableConfig = $this->createMock(TableConfig::class);

        $tableConfig->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(
                ['customObjectId'],
                ['search']
            )->will($this->onConsecutiveCalls(
                44,
                null
            ));

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->customItemModel->getTableData($tableConfig);
    }

    public function testGetCountForTable(): void
    {
        $tableConfig = $this->createMock(TableConfig::class);
        $expr        = $this->createMock(Expr::class);

        $tableConfig->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(
                ['customObjectId'],
                ['search']
            )->will($this->onConsecutiveCalls(
                44,
                null
            ));

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomItem::TABLE_ALIAS],
                ['the select count expr']
            );

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
        $tableConfig = $this->createMock(TableConfig::class);

        $tableConfig->expects($this->exactly(2))
            ->method('getParameter')
            ->withConsecutive(
                ['customObjectId'],
                ['search']
            )->will($this->onConsecutiveCalls(
                44,
                'Item A'
            ));

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->queryBuilder->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['alias_a']);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(
                [CustomItem::TABLE_ALIAS],
                ['alias_a.name as value, alias_a.id']
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->withConsecutive(
                [CustomItem::TABLE_ALIAS.'.name LIKE %:search%'],
                [CustomItem::TABLE_ALIAS.'.createdBy', 22]
            );

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['customObjectId', 44],
                ['search', 'Item A']
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
        $customFieldValue  = $this->createMock(CustomFieldValueInterface::class);
        $customFieldValues = new ArrayCollection([$customFieldValue]);

        $this->customItem->expects($this->once())
            ->method('getCustomFieldValues')
            ->willReturn(new ArrayCollection());

        $this->customFieldValueModel->expects($this->once())
            ->method('createValuesForItem')
            ->with($this->customItem)
            ->willReturn($customFieldValues);

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
