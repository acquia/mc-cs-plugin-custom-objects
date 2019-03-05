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

use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ListController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemSessionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Tests\Controller\ControllerDependenciesTrait;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RequestStack;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;

class ListControllerTest extends \PHPUnit_Framework_TestCase
{
    use ControllerDependenciesTrait;

    private const OBJECT_ID = 33;

    private const PAGE = 3;

    private $customItemModel;
    private $customObjectModel;
    private $sessionProvider;
    private $permissionProvider;
    private $routeProvider;
    private $requestStack;
    private $request;

    /**
     * @var ListController
     */
    private $listController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack       = $this->createMock(RequestStack::class);
        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->sessionProvider    = $this->createMock(CustomItemSessionProvider::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomItemRouteProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->listController     = new ListController(
            $this->requestStack,
            $this->sessionProvider,
            $this->customItemModel,
            $this->customObjectModel,
            $this->permissionProvider,
            $this->routeProvider
        );

        $this->addSymfonyDependencies($this->listController);

        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
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
            ['limit', $pageLimit, false, $pageLimit],
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
            $customObjectFilter = $tableConfig->getFilter(CustomItem::class, 'customObject');
            $this->assertSame(CustomItem::class, $customObjectFilter->getEntityName());
            $this->assertSame('customObject', $customObjectFilter->getColumnName());
            $this->assertSame(self::OBJECT_ID, $customObjectFilter->getValue());
            $this->assertSame('eq', $customObjectFilter->getExpression());

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
