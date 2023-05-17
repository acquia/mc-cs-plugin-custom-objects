<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\CancelController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $sessionProviderFactory;
    private $sessionProvider;
    private $routeProvider;
    private $customItemModel;

    /**
     * @var CancelController
     */
    private $cancelController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionProviderFactory = $this->createMock(SessionProviderFactory::class);
        $this->sessionProvider        = $this->createMock(SessionProvider::class);
        $this->routeProvider          = $this->createMock(CustomItemRouteProvider::class);
        $this->customItemModel        = $this->createMock(CustomItemModel::class);
        $this->request                = $this->createMock(Request::class);
        $this->requestStack           = $this->createMock(RequestStack::class);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->cancelController       = new CancelController();

        $this->addSymfonyDependencies($this->cancelController);

        $this->sessionProviderFactory->method('createItemProvider')->willReturn($this->sessionProvider);
    }

    public function testCancelAction(): void
    {
        $pageNumber = 4;

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->customItemModel->expects($this->never())
            ->method('fetchEntity');

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, $pageNumber)
            ->willReturn('some/route');

        $this->cancelController->cancelAction(
            $this->requestStack,
            $this->sessionProviderFactory,
            $this->routeProvider,
            $this->customItemModel,
            self::OBJECT_ID
        );
    }

    public function testCancelActionWithEntityUnlock(): void
    {
        $pageNumber     = 2;
        $customItemId   = 4;
        $customItem     = new CustomItem(new CustomObject());

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->with($customItemId)
            ->willReturn($customItem);

        $this->customItemModel->expects($this->once())
            ->method('unlockEntity')
            ->with($customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID, $pageNumber)
            ->willReturn('some/route');

        $this->cancelController->cancelAction(
            $this->requestStack,
            $this->sessionProviderFactory,
            $this->routeProvider,
            $this->customItemModel,
            self::OBJECT_ID,
            $customItemId
        );
    }
}
