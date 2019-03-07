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

namespace MauticPlugin\CustomObjectsBundle\Tests\Controller\CustomItem;

use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LinkController;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Tests\Controller\ControllerTestCase;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;

class LinkControllerTest extends ControllerTestCase
{
    private const ITEM_ID = 22;

    private const ENTITY_ID = 33;

    private const ENTITY_TYPE = 'contact';

    private $customItemModel;
    private $customItemXrefContactModel;
    private $flashBag;
    private $permissionProvider;

    /**
     * @var LinkController
     */
    private $linkController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel            = $this->createMock(CustomItemModel::class);
        $this->customItemXrefContactModel = $this->createMock(CustomItemXrefContactModel::class);
        $this->flashBag                   = $this->createMock(FlashBag::class);
        $this->permissionProvider         = $this->createMock(CustomItemPermissionProvider::class);
        $this->linkController             = new LinkController(
            $this->customItemModel,
            $this->customItemXrefContactModel,
            $this->permissionProvider,
            $this->flashBag
        );

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

        $this->linkController->saveAction(self::ITEM_ID, self::ENTITY_TYPE, self::ENTITY_ID);
    }

    public function testSaveAction(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->createMock(CustomItem::class));

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->customItemXrefContactModel->expects($this->once())
            ->method('linkContact')
            ->with(self::ITEM_ID, self::ENTITY_ID);

        $this->linkController->saveAction(self::ITEM_ID, self::ENTITY_TYPE, self::ENTITY_ID);
    }
}
