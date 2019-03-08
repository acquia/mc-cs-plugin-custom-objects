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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectSessionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;

class ListControllerTest extends ControllerTestCase
{
    private const PAGE = 3;

    private $customObjectModel;
    private $sessionProvider;
    private $permissionProvider;
    private $routeProvider;

    /**
     * @var ListController
     */
    private $listController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack       = $this->createMock(RequestStack::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->sessionProvider    = $this->createMock(CustomObjectSessionProvider::class);
        $this->permissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomObjectRouteProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->listController     = new ListController(
            $this->requestStack,
            $this->sessionProvider,
            $this->customObjectModel,
            $this->permissionProvider,
            $this->routeProvider
        );

        $this->addSymfonyDependencies($this->listController);

        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
    }

    public function testListActionIfForbidden(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll')
            ->will($this->throwException(new ForbiddenException('view')));

        $this->customObjectModel->expects($this->never())
            ->method('getTableData');

        $this->expectException(AccessDeniedHttpException::class);

        $this->listController->listAction(self::PAGE);
    }

    public function testListAction(): void
    {
        $pageLimit = 10;

        $this->request->method('get')->will($this->returnValueMap([
            ['limit', $pageLimit, false, $pageLimit],
        ]));

        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll');

        $this->customObjectModel->expects($this->once())
            ->method('getTableData');

        $this->sessionProvider->expects($this->once())
            ->method('getPageLimit')
            ->willReturn($pageLimit);

        $this->sessionProvider->expects($this->once())
            ->method('getOrderBy')
            ->willReturn(CustomObjectRepository::TABLE_ALIAS.'.id');

        $this->sessionProvider->expects($this->once())
            ->method('getOrderByDir')
            ->willReturn('DESC');

        $assertTableConfig = function (TableConfig $tableConfig) {
            $this->assertSame(10, $tableConfig->getLimit());
            $this->assertSame(20, $tableConfig->getOffset());
            $this->assertSame(CustomObjectRepository::TABLE_ALIAS.'.id', $tableConfig->getOrderBy());
            $this->assertSame('DESC', $tableConfig->getOrderDirection());

            return true;
        };

        $this->customObjectModel->expects($this->once())
            ->method('getTableData')
            ->with($this->callback($assertTableConfig));

        $this->customObjectModel->expects($this->once())
            ->method('getCountForTable')
            ->with($this->callback($assertTableConfig));

        $this->sessionProvider->expects($this->once())
            ->method('setPage')
            ->with(self::PAGE);

        $this->sessionProvider->expects($this->once())
            ->method('setPageLimit')
            ->with($pageLimit);

        $this->listController->listAction(self::PAGE);
    }
}
