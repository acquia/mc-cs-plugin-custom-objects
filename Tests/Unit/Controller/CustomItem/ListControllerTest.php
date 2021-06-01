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

use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ListController;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ListControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private const PAGE = 3;

    private $customItemModel;
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

        $sessionProviderFactory   = $this->createMock(SessionProviderFactory::class);
        $this->requestStack       = $this->createMock(RequestStack::class);
        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->sessionProvider    = $this->createMock(SessionProvider::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomItemRouteProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->listController     = new ListController(
            $this->requestStack,
            $sessionProviderFactory,
            $this->customItemModel,
            $this->customObjectModel,
            $this->permissionProvider,
            $this->routeProvider
        );

        $this->addSymfonyDependencies($this->listController);

        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
        $sessionProviderFactory->method('createItemProvider')->willReturn($this->sessionProvider);
    }

    public function testListActionIfCustomObjectNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->customItemModel->expects($this->never())
            ->method('getTableData');

        $this->listController->listAction(self::OBJECT_ID, self::PAGE);
    }

    public function testListActionIfForbidden(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll')
            ->will($this->throwException(new ForbiddenException('view')));

        $this->customObjectModel->expects($this->never())
            ->method('fetchEntity');

        $this->expectException(AccessDeniedHttpException::class);

        $this->listController->listAction(self::OBJECT_ID, self::PAGE);
    }

    public function testListAction(): void
    {
        $pageLimit    = 10;
        $customObject = $this->createMock(CustomObject::class);

        $this->request->method('get')->will($this->returnValueMap([
            ['limit', $pageLimit, $pageLimit],
        ]));

        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customObject);

        $this->sessionProvider->expects($this->once())
            ->method('getPageLimit')
            ->willReturn($pageLimit);

        $this->sessionProvider->expects($this->once())
            ->method('getOrderBy')
            ->willReturn('e.id');

        $this->sessionProvider->expects($this->once())
            ->method('getOrderByDir')
            ->willReturn('DESC');

        $assertTableConfig = function (TableConfig $tableConfig) {
            $this->assertSame(10, $tableConfig->getLimit());
            $this->assertSame(20, $tableConfig->getOffset());
            $this->assertSame('e.id', $tableConfig->getOrderBy());
            $this->assertSame('DESC', $tableConfig->getOrderDirection());
            $this->assertSame(self::OBJECT_ID, $tableConfig->getParameter('customObjectId'));
            $this->assertSame('', $tableConfig->getParameter('filterEntityType'));
            $this->assertSame(0, $tableConfig->getParameter('filterEntityId'));
            $this->assertSame('', $tableConfig->getParameter('search'));

            return true;
        };

        $this->customItemModel->expects($this->once())
            ->method('getTableData')
            ->with($this->callback($assertTableConfig));

        $this->customItemModel->expects($this->once())
            ->method('getCountForTable')
            ->with($this->callback($assertTableConfig));

        $this->sessionProvider->expects($this->once())
            ->method('setPage')
            ->with(self::PAGE);

        $this->sessionProvider->expects($this->once())
            ->method('setPageLimit')
            ->with($pageLimit);

        $this->listController->listAction(self::OBJECT_ID, self::PAGE);
    }

    public function testListActionWithQueryParamAndAjax(): void
    {
        $pageLimit    = 10;
        $search       = 'Search some';
        $customObject = $this->createMock(CustomObject::class);

        $this->request->query = new ParameterBag(
            [
                'orderby' => 'e.name',
            ]
        );

        $this->request->method('get')->will($this->returnValueMap([
            ['limit', $pageLimit, $pageLimit],
            ['search', '', $search],
        ]));

        $this->request->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customObject);

        $this->sessionProvider->expects($this->once())
            ->method('getPageLimit')
            ->willReturn($pageLimit);

        $this->sessionProvider->expects($this->once())
            ->method('getOrderBy')
            ->willReturn('e.id');

        $this->sessionProvider->expects($this->once())
            ->method('getOrderByDir')
            ->willReturn('DESC');

        $this->sessionProvider->expects($this->once())
            ->method('setOrderBy');

        $this->sessionProvider->expects($this->once())
            ->method('setOrderByDir');

        $assertTableConfig = function (TableConfig $tableConfig) use ($search) {
            $this->assertSame(10, $tableConfig->getLimit());
            $this->assertSame(20, $tableConfig->getOffset());
            $this->assertSame('e.name', $tableConfig->getOrderBy());
            $this->assertSame('ASC', $tableConfig->getOrderDirection());
            $this->assertSame(self::OBJECT_ID, $tableConfig->getParameter('customObjectId'));
            $this->assertSame('', $tableConfig->getParameter('filterEntityType'));
            $this->assertSame(0, $tableConfig->getParameter('filterEntityId'));
            $this->assertSame($search, $tableConfig->getParameter('search'));

            return true;
        };

        $this->customItemModel->expects($this->once())
            ->method('getTableData')
            ->with($this->callback($assertTableConfig));

        $this->customItemModel->expects($this->once())
            ->method('getCountForTable')
            ->with($this->callback($assertTableConfig));

        $this->sessionProvider->expects($this->once())
            ->method('setPage')
            ->with(self::PAGE);

        $this->sessionProvider->expects($this->once())
            ->method('setPageLimit')
            ->with($pageLimit);

        $this->listController->listAction(self::OBJECT_ID, self::PAGE);
    }
}
