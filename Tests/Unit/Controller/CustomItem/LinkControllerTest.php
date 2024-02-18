<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LinkController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use UnexpectedValueException;

class LinkControllerTest extends ControllerTestCase
{
    private const ITEM_ID = 22;

    private const ENTITY_ID = 33;

    private const ENTITY_TYPE = 'contact';

    private $customItemModel;
    private $flashBag;
    private $permissionProvider;

    /**
     * @var LinkController
     */
    private $linkController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->flashBag           = $this->createMock(FlashBag::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->linkController     = new LinkController();
        $this->addSymfonyDependencies($this->linkController);
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

        $this->linkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }

    public function testSaveActionIfCustomItemIsLinkedAlready(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will(
                $this->throwException(
                    new UniqueConstraintViolationException(
                        'a message',
                        $this->createMock(DriverException::class)
                    )
                )
            );

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with(
                'custom.item.error.link.exists.already',
                ['%itemId%' => self::ITEM_ID, '%entityType%' => self::ENTITY_TYPE, '%entityId%' => self::ENTITY_ID],
                FlashBag::LEVEL_ERROR
            );

        $this->linkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }

    public function testSaveActionIfCustomItemForbidden(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->createMock(CustomItem::class));

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('You do not have permission to edit', [], FlashBag::LEVEL_ERROR);

        $this->linkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }

    public function testSaveActionIfCustomItemLinkedToUnknownEntityType(): void
    {
        $customItem = $this->createMock(CustomItem::class);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->customItemModel->expects($this->once())
            ->method('linkEntity')
            ->with($customItem, 'unicorn', self::ENTITY_ID)
            ->will($this->throwException(new UnexpectedValueException('Entity unicorn cannot be linked to a custom item')));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('Entity unicorn cannot be linked to a custom item', [], FlashBag::LEVEL_ERROR);

        $this->linkController->saveAction(
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
        $customItem = $this->createMock(CustomItem::class);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->customItemModel->expects($this->once())
            ->method('linkEntity')
            ->with($customItem, self::ENTITY_TYPE, self::ENTITY_ID);

        $this->linkController->saveAction(
            $this->customItemModel,
            $this->permissionProvider,
            $this->flashBag,
            self::ITEM_ID,
            self::ENTITY_TYPE,
            self::ENTITY_ID
        );
    }
}
