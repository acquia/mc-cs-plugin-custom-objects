<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\DeleteController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private const ITEM_ID = 22;

    private $customItemModel;
    private $sessionProvider;
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

        $doctrine             = $this->createMock(ManagerRegistry::class);
        $modelFactory         = $this->createMock(ModelFactory::class);
        $userHelper           = $this->createMock(UserHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $translator           = $this->createMock(Translator::class);
        $this->flashBag       = $this->createMock(FlashBag::class);
        $this->requestStack   = $this->createMock(RequestStack::class);
        $security             = $this->createMock(CorePermissions::class);
        $sessionProviderFactory   = $this->createMock(SessionProviderFactory::class);
        $this->customItemModel    = $this->createMock(CustomItemModel::class);
        $this->sessionProvider    = $this->createMock(SessionProvider::class);
        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomItemRouteProvider::class);
        $this->request            = $this->createMock(Request::class);
        $this->deleteController   = new DeleteController(
            $doctrine,
            $modelFactory,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $translator,
            $this->flashBag,
            $this->requestStack,
            $security,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customItemModel,
            $sessionProviderFactory
        );

        $this->addSymfonyDependencies($this->deleteController);

        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
        $sessionProviderFactory->method('createItemProvider')->willReturn($this->sessionProvider);
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

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn(3);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, 3);

        $this->deleteController->deleteAction(self::OBJECT_ID, self::ITEM_ID);
    }
}
