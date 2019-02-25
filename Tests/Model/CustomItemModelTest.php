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

namespace MauticPlugin\CustomObjectsBundle\Tests\Model;

use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use Mautic\CoreBundle\Helper\UserHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;

class CustomItemModelTest extends \PHPUnit_Framework_TestCase
{
    private $customItem;
    private $user;
    private $entityManager;
    private $customItemRepository;
    private $customItemPermissionProvider;
    private $userHelper;
    private $customFieldModel;
    private $customFieldValueModel;
    private $customFieldTypeProvider;
    private $dispatcher;
    private $customItemModel;

    protected function setUp()
    {
        parent::setUp();

        $this->customItem                   = $this->createMock(CustomItem::class);
        $this->user                         = $this->createMock(User::class);
        $this->entityManager                = $this->createMock(EntityManager::class);
        $this->customItemRepository         = $this->createMock(CustomItemRepository::class);
        $this->customItemPermissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->userHelper                   = $this->createMock(UserHelper::class);
        $this->customFieldModel             = $this->createMock(CustomFieldModel::class);
        $this->customFieldValueModel        = $this->createMock(CustomFieldValueModel::class);
        $this->customFieldTypeProvider      = $this->createMock(CustomFieldTypeProvider::class);
        $this->dispatcher                   = $this->createMock(EventDispatcherInterface::class);
        $this->customItemModel              = new CustomItemModel(
            $this->entityManager,
            $this->customItemRepository,
            $this->customItemPermissionProvider,
            $this->userHelper,
            $this->customFieldModel,
            $this->customFieldValueModel,
            $this->customFieldTypeProvider,
            $this->dispatcher
        );
    }

    public function testSaveNew()
    {
        $this->user->expects($this->exactly(2))->method('getId')->willReturn(55);
        $this->user->expects($this->exactly(2))->method('getName')->willReturn('John Doe');
        $this->userHelper->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->customItem->expects($this->exactly(2))->method('isNew')->willReturn(true);
        $this->customItem->expects($this->once())->method('setCreatedBy')->with(55);
        $this->customItem->expects($this->once())->method('setCreatedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateAdded');
        $this->customItem->expects($this->once())->method('setModifiedBy')->with(55);
        $this->customItem->expects($this->once())->method('setModifiedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateModified');
        $this->entityManager->expects($this->at(0))->method('persist')->with($this->customItem);
        $this->customItem->expects($this->once())->method('getCustomFieldValues')->willReturn(new ArrayCollection());
        $this->customItem->expects($this->once())->method('getContactReferences')->willReturn(new ArrayCollection());
        $this->customItem->expects($this->once())->method('recordCustomFieldValueChanges');
        $this->dispatcher->expects($this->at(0))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $this->isInstanceOf(CustomItemEvent::class));
        $this->entityManager->expects($this->at(1))->method('flush');
        $this->dispatcher->expects($this->at(1))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $this->isInstanceOf(CustomItemEvent::class));

        $this->assertSame($this->customItem, $this->customItemModel->save($this->customItem));
    }

    public function testSaveEdit()
    {
        $customFieldValue = $this->createMock(CustomFieldValueText::class);
        $contactXref      = $this->createMock(CustomItemXrefContact::class);
        $this->user->expects($this->once())->method('getId')->willReturn(55);
        $this->user->expects($this->once())->method('getName')->willReturn('John Doe');
        $this->userHelper->expects($this->once())->method('getUser')->willReturn($this->user);
        $this->customItem->expects($this->exactly(2))->method('isNew')->willReturn(false);
        $this->customItem->expects($this->once())->method('setModifiedBy')->with(55);
        $this->customItem->expects($this->once())->method('setModifiedByUser')->with('John Doe');
        $this->customItem->expects($this->once())->method('setDateModified');
        $this->entityManager->expects($this->at(0))->method('persist')->with($this->customItem);
        $this->customItem->expects($this->once())->method('getCustomFieldValues')->willReturn(new ArrayCollection([$customFieldValue]));
        $this->customItem->expects($this->once())->method('getContactReferences')->willReturn(new ArrayCollection([$contactXref]));
        $this->customItem->expects($this->once())->method('recordCustomFieldValueChanges');
        $this->customFieldValueModel->expects($this->once())->method('save')->with($customFieldValue);
        $this->entityManager->expects($this->at(1))->method('persist')->with($contactXref);
        $this->dispatcher->expects($this->at(0))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $this->isInstanceOf(CustomItemEvent::class));
        $this->entityManager->expects($this->at(2))->method('flush');
        $this->dispatcher->expects($this->at(1))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $this->isInstanceOf(CustomItemEvent::class));

        $this->assertSame($this->customItem, $this->customItemModel->save($this->customItem));
    }

    public function testDelete()
    {
        $this->customItem->expects($this->once())->method('getId')->willReturn(34);
        $this->dispatcher->expects($this->at(0))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_PRE_DELETE, $this->isInstanceOf(CustomItemEvent::class));
        $this->entityManager->expects($this->at(0))->method('remove')->with($this->customItem);
        $this->entityManager->expects($this->at(1))->method('flush');
        $this->dispatcher->expects($this->at(1))->method('dispatch')->with(CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE, $this->isInstanceOf(CustomItemEvent::class));

        $this->customItemModel->delete($this->customItem);
    }
}
