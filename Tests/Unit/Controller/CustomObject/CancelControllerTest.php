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
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\CancelController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelControllerTest extends ControllerTestCase
{
    private $sessionProvider;
    private $routeProvider;
    private $customObjectModel;

    /**
     * @var CancelController
     */
    private $cancelController;

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
        $sessionProviderFactory  = $this->createMock(SessionProviderFactory::class);
        $this->sessionProvider   = $this->createMock(SessionProvider::class);
        $this->routeProvider     = $this->createMock(CustomObjectRouteProvider::class);
        $this->customObjectModel = $this->createMock(CustomObjectModel::class);

        $this->cancelController = new CancelController(
            $doctrine,
            $modelFactory,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $translator,
            $flashBag,
            $this->requestStack,
            $security,
            $sessionProviderFactory,
            $this->routeProvider,
            $this->customObjectModel
        );

        $this->addSymfonyDependencies($this->cancelController);
        $sessionProviderFactory->method('createObjectProvider')->willReturn($this->sessionProvider);
    }

    public function testCancelAction(): void
    {
        $pageNumber = 4;

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->customObjectModel->expects($this->never())
            ->method('fetchEntity');

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with($pageNumber)
            ->willReturn('some/route');

        $this->cancelController->cancelAction(null);
    }

    public function testCancelActionWithEntityUnlock(): void
    {
        $pageNumber     = 2;
        $customObjectId = 3;
        $customObject   = new CustomObject();

        $this->sessionProvider->expects($this->once())
            ->method('getPage')
            ->willReturn($pageNumber);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with($customObjectId)
            ->willReturn($customObject);

        $this->customObjectModel->expects($this->once())
            ->method('unlockEntity')
            ->with($customObject);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with($pageNumber)
            ->willReturn('some/route');

        $this->cancelController->cancelAction($customObjectId);
    }
}
