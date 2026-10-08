<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

        $doctrine             = $this->createMock(ManagerRegistry::class);
        $modelFactory         = $this->createMock(ModelFactory::class);
        $userHelper           = $this->createMock(UserHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $translator           = $this->createMock(Translator::class);
        $flashBag             = $this->createMock(FlashBag::class);
        $this->requestStack   = $this->createMock(RequestStack::class);
        $security             = $this->createMock(CorePermissions::class);
        $sessionProviderFactory   = $this->createMock(SessionProviderFactory::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->sessionProvider    = $this->createMock(SessionProvider::class);
        $this->permissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomObjectRouteProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->listController     = new ListController(
            $doctrine,
            $modelFactory,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $translator,
            $flashBag,
            $this->requestStack,
            $security,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customObjectModel,
            $sessionProviderFactory
        );

        $this->addSymfonyDependencies($this->listController);

        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
        $sessionProviderFactory->method('createObjectProvider')->willReturn($this->sessionProvider);
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
            ['limit', $pageLimit, $pageLimit],
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
            ->willReturn(CustomObject::TABLE_ALIAS.'.id');

        $this->sessionProvider->expects($this->once())
            ->method('getOrderByDir')
            ->willReturn('DESC');

        $assertTableConfig = function (TableConfig $tableConfig) {
            $this->assertSame(10, $tableConfig->getLimit());
            $this->assertSame(20, $tableConfig->getOffset());
            $this->assertSame(CustomObject::TABLE_ALIAS.'.id', $tableConfig->getOrderBy());
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

    public function testListActionWithOrderByQueryParamAndAjax(): void
    {
        $pageLimit = 10;

        $this->request->query = new ParameterBag(
            [
                'orderby' => 'e.name',
            ]
        );

        $this->request->method('get')->will($this->returnValueMap([
            ['limit', $pageLimit, $pageLimit],
        ]));

        $this->request->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll');

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

        $assertTableConfig = function (TableConfig $tableConfig) {
            $this->assertSame(10, $tableConfig->getLimit());
            $this->assertSame(20, $tableConfig->getOffset());
            $this->assertSame('e.name', $tableConfig->getOrderBy());
            $this->assertSame('ASC', $tableConfig->getOrderDirection());

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
