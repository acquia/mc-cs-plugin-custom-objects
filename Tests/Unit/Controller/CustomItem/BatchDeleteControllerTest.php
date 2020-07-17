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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Mautic\CoreBundle\Service\FlashBag;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\BatchDeleteController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BatchDeleteControllerTest extends ControllerTestCase
{
    private $customItemModel;
    private $sessionProvider;
    private $flashBag;
    private $permissionProvider;
    private $routeProvider;

    /**
     * @var BatchDeleteController
     */
    private $batchDeleteController;

    protected function setUp(): void
    {
        parent::setUp();

        $sessionProviderFactory      = $this->createMock(SessionProviderFactory::class);
        $this->requestStack          = $this->createMock(RequestStack::class);
        $this->customItemModel       = $this->createMock(CustomItemModel::class);
        $this->sessionProvider       = $this->createMock(SessionProvider::class);
        $this->flashBag              = $this->createMock(FlashBag::class);
        $this->permissionProvider    = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider         = $this->createMock(CustomItemRouteProvider::class);
        $this->request               = $this->createMock(Request::class);
        $this->batchDeleteController = new BatchDeleteController(
            $this->requestStack,
            $this->customItemModel,
            $sessionProviderFactory,
            $this->permissionProvider,
            $this->routeProvider,
            $this->flashBag
        );

        $this->addSymfonyDependencies($this->batchDeleteController);

        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $sessionProviderFactory->method('createItemProvider')->willReturn($this->sessionProvider);
    }

    public function testDeleteActionIfCustomItemNotFound(): void
    {
        $this->request->expects($this->at(0))
            ->method('get')
            ->with('ids', '[]')
            ->willReturn('[13, 14]');

        $this->customItemModel->expects($this->exactly(2))
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('custom.item.error.items.not.found', ['%ids%' => '13,14'], FlashBag::LEVEL_ERROR);

        $this->batchDeleteController->deleteAction(33);
    }

    public function testDeleteActionIfCustomItemForbidden(): void
    {
        $this->request->expects($this->at(0))
            ->method('get')
            ->with('ids', '[]')
            ->willReturn('[13, 14]');

        $this->customItemModel->expects($this->exactly(2))
            ->method('fetchEntity')
            ->willReturn($this->createMock(CustomItem::class));

        $this->permissionProvider->expects($this->exactly(2))
            ->method('canDelete')
            ->will($this->throwException(new ForbiddenException('delete')));

        $this->customItemModel->expects($this->never())
            ->method('delete');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('custom.item.error.items.denied', ['%ids%' => '13,14'], FlashBag::LEVEL_ERROR);

        $this->batchDeleteController->deleteAction(33);
    }

    public function testDeleteAction(): void
    {
        $customItem13 = $this->createMock(CustomItem::class);
        $customItem14 = $this->createMock(CustomItem::class);

        $this->request->expects($this->at(0))
            ->method('get')
            ->with('ids', '[]')
            ->willReturn('[13, 14]');

        $this->customItemModel->expects($this->at(0))
            ->method('fetchEntity')
            ->with(13)
            ->willReturn($customItem13);

        $this->customItemModel->expects($this->at(1))
            ->method('delete')
            ->with($customItem13);

        $this->customItemModel->expects($this->at(2))
            ->method('fetchEntity')
            ->with(14)
            ->willReturn($customItem14);

        $this->customItemModel->expects($this->at(3))
            ->method('delete')
            ->with($customItem14);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('mautic.core.notice.batch_deleted', ['%count%' => 2]);

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(33, 3);

        $this->batchDeleteController->deleteAction(33);
    }
}
