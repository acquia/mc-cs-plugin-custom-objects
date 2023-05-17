<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\UnlinkController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use UnexpectedValueException;

class UnlinkControllerTest extends ControllerTestCase
{
    public const ITEM_ID = 22;

    public const ENTITY_ID = 33;

    public const ENTITY_TYPE = 'contact';

    /**
     * @var MockObject|CustomItemModel
     */
    private $customItemModel;

    /**
     * @var MockObject|FlashBag
     */
    private $flashBag;

    /**
     * @var MockObject|CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var UnlinkController
     */
    private $unlinkController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel            = $this->createMock(CustomItemModel::class);
        $this->flashBag                   = $this->createMock(FlashBag::class);
        $this->permissionProvider         = $this->createMock(CustomItemPermissionProvider::class);
        $this->unlinkController           = new UnlinkController();

        $this->addSymfonyDependencies($this->unlinkController);
    }

    public function testSaveActionIfCustomItemNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('Item not found message')));

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('Item not found message', [], FlashBag::LEVEL_ERROR);

        $this->customItemModel->expects($this->never())
            ->method('unlinkEntity');

        $this->unlinkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }

    public function testSaveActionIfForbidden(): void
    {
        $customItem = $this->createMock(CustomItem::class);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($customItem)
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('You do not have permission to edit', [], FlashBag::LEVEL_ERROR);

        $this->customItemModel->expects($this->never())
            ->method('unlinkEntity');

        $this->unlinkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }

    public function testSaveActionIfEntityTypeNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn(new CustomItem(new CustomObject()));

        $this->customItemModel->expects($this->once())
            ->method('unlinkEntity')
            ->will($this->throwException(new UnexpectedValueException('Entity unicorn cannot be linked to a custom item')));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('Entity unicorn cannot be linked to a custom item', [], FlashBag::LEVEL_ERROR);

        $this->unlinkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            'unicorn',
            self::ENTITY_ID
        );
    }

    public function testSaveAction(): void
    {
        $customItem = new class(new CustomObject()) extends CustomItem {
            public function getId()
            {
                return UnlinkControllerTest::ITEM_ID;
            }
        };

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->customItemModel->expects($this->once())
            ->method('unlinkEntity')
            ->with($customItem, self::ENTITY_TYPE, self::ENTITY_ID);

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->unlinkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }

    public function testSaveActionWithChildItem(): void
    {
        $customObject = new class() extends CustomObject {
        };

        $childCustomObject = new class() extends CustomObject {
        };

        $customObject->setRelationshipObject($childCustomObject);

        $customItem = new class($customObject) extends CustomItem {
            public function getId()
            {
                return UnlinkControllerTest::ITEM_ID;
            }

            public function findChildCustomItem(): CustomItem
            {
                return new class($this->getCustomObject()->getRelationshipObject()) extends CustomItem {
                };
            }
        };

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->customItemModel->expects($this->once())
            ->method('unlinkEntity')
            ->with($customItem, self::ENTITY_TYPE, self::ENTITY_ID);

        $this->customItemModel->expects($this->once())
            ->method('delete')
            ->with($this->callback(
                function (CustomItem $childCustomItem) use ($childCustomObject) {
                    Assert::assertSame($childCustomObject, $childCustomItem->getCustomObject());

                    return true;
                }
            ));

        $this->unlinkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }
}
