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
use MauticPlugin\CustomObjectsBundle\DTO\TableFilterConfig;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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

    public function testGetTableData(): void
    {
        $tableConfig       = $this->createMock(TableConfig::class);
        $tableFilterConfig = $this->createMock(TableFilterConfig::class);

        $tableConfig->expects($this->once())
            ->method('getFilter')
            ->with(CustomItem::class, 'customObject')
            ->willReturn($tableFilterConfig);

        $tableFilterConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(99);

        $this->customItemRepository->expects($this->once())
            ->method('getTableDataQuery')
            ->with($tableConfig)
            ->willReturn($this->queryBuilder);

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->customItemRepository->expects($this->once())
            ->method('applyOwnerId')
            ->with($this->queryBuilder, 22);

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
        $tableConfig       = $this->createMock(TableConfig::class);
        $tableFilterConfig = $this->createMock(TableFilterConfig::class);

        $tableConfig->expects($this->once())
            ->method('getFilter')
            ->with(CustomItem::class, 'customObject')
            ->willReturn($tableFilterConfig);

        $tableFilterConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(99);

        $this->customItemRepository->expects($this->once())
            ->method('getTableCountQuery')
            ->with($tableConfig)
            ->willReturn($this->queryBuilder);

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->customItemRepository->expects($this->once())
            ->method('applyOwnerId')
            ->with($this->queryBuilder, 22);

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
        $tableConfig       = $this->createMock(TableConfig::class);
        $tableFilterConfig = $this->createMock(TableFilterConfig::class);

        $tableConfig->expects($this->once())
            ->method('getFilter')
            ->with(CustomItem::class, 'customObject')
            ->willReturn($tableFilterConfig);

        $tableFilterConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(99);

        $this->customItemRepository->expects($this->once())
            ->method('getTableDataQuery')
            ->with($tableConfig)
            ->willReturn($this->queryBuilder);

        $this->customItemPermissionProvider->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new ForbiddenException('viewother')));

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn(22);

        $this->customItemRepository->expects($this->once())
            ->method('applyOwnerId')
            ->with($this->queryBuilder, 22);

        $this->queryBuilder->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['alias_a']);

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('alias_a.name as value, alias_a.id');

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([]);

        $this->customItemModel->getLookupData($tableConfig);
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
}
