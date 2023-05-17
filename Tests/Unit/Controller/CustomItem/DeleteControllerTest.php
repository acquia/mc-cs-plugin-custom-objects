<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\NotificationModel;
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
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

        $this->sessionProviderFactory = $this->createMock(SessionProviderFactory::class);
        $this->customItemModel        = $this->createMock(CustomItemModel::class);
        $this->sessionProvider        = $this->createMock(SessionProvider::class);
        $this->flashBag               = $this->createMock(FlashBag::class);
        $this->permissionProvider     = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider          = $this->createMock(CustomItemRouteProvider::class);
        $this->request                = $this->createMock(Request::class);
        $this->requestStack           = $this->createMock(RequestStack::class);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->translator             = $this->createMock(Translator::class);
        $this->security               = $this->createMock(CorePermissions::class);
        $this->modelFactory           = $this->createMock(ModelFactory::class);
        $this->model                  = $this->createMock(NotificationModel::class);

        $this->deleteController       = new DeleteController();
        $this->deleteController->setTranslator($this->translator);
        $this->deleteController->setSecurity($this->security);
        $this->deleteController->setModelFactory($this->modelFactory);

        $this->addSymfonyDependencies($this->deleteController);

        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
        $this->sessionProviderFactory->method('createItemProvider')->willReturn($this->sessionProvider);
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

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Item not found message');

        $post  = $this->createMock(ParameterBag::class);
        $this->request->request = $post;
        $post->expects($this->once())
            ->method('all')
            ->willReturn([]);

        $this->deleteController->deleteAction(
            $this->requestStack,
            $this->customItemModel,
            $this->sessionProviderFactory,
            $this->flashBag,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID,
            self::ITEM_ID
        );
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

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->expectException(AccessDeniedHttpException::class);

        $this->deleteController->deleteAction(
            $this->requestStack,
            $this->customItemModel,
            $this->sessionProviderFactory,
            $this->flashBag,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID,
            self::ITEM_ID
        );
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

        $this->modelFactory->expects($this->once())
            ->method('getModel')
            ->willReturn($this->model);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('get')
            ->willReturn('test');

        $this->request->expects($this->exactly(2))
            ->method('getSession')
            ->willReturn($session);

        $this->model->expects($this->once())
            ->method('getNotificationContent')
            ->willReturn([[], 'test', 'test']);

        $this->deleteController->deleteAction(
            $this->requestStack,
            $this->customItemModel,
            $this->sessionProviderFactory,
            $this->flashBag,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID,
            self::ITEM_ID
        );
    }
}
