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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\DeleteController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mautic\CoreBundle\Service\FlashBag;

class DeleteControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private const ITEM_ID = 22;

    private $customItemModel;
    private $sessionprovider;
    private $flashBag;
    private $permissionProvider;
    private $routeProvider;

    /**
     * @var DeleteController
     */
    private $deleteController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->sessionprovider    = $this->createMock(CustomItemSessionProvider::class);
        $this->flashBag           = $this->createMock(FlashBag::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomItemRouteProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->deleteController   = new DeleteController(
            $this->customItemModel,
            $this->sessionprovider,
            $this->flashBag,
            $this->permissionProvider,
            $this->routeProvider
        );

        $this->addSymfonyDependencies($this->deleteController);

        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
    }

    public function testDeleteActionIfCustomItemNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('Item not found message')));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->flashBag->expects($this->never())
            ->method('add');

        $this->deleteController->deleteAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testDeleteActionIfCustomItemForbidden(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->createMock(CustomItem::class));

        $this->permissionProvider->expects($this->once())
            ->method('canDelete')
            ->will($this->throwException(new ForbiddenException('delete')));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->flashBag->expects($this->never())
            ->method('add');

        $this->expectException(AccessDeniedHttpException::class);

        $this->deleteController->deleteAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testDeleteAction(): void
    {
        $customItem = $this->createMock(CustomItem::class);

        $customItem->method('getId')->willReturn(self::ITEM_ID);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::ITEM_ID)
            ->willReturn($customItem);

        $this->customItemModel->expects($this->once())
            ->method('delete')
            ->with($customItem);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('mautic.core.notice.deleted');

        $this->sessionprovider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, 3);

        $this->deleteController->deleteAction(self::OBJECT_ID, self::ITEM_ID);
    }
}
