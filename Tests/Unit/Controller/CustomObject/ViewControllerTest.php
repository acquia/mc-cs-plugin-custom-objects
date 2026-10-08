<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ViewController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ViewControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $customObjectModel;
    private $auditLog;
    private $permissionProvider;
    private $routeProvider;
    private $formFactory;
    private $form;
    private $customObject;

    /**
     * @var ViewController
     */
    private $viewController;

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
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->auditLog           = $this->createMock(AuditLogModel::class);
        $this->permissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomObjectRouteProvider::class);
        $this->formFactory        = $this->createMock(FormFactoryInterface::class);
        $this->form               = $this->createMock(FormInterface::class);
        $this->customObject       = $this->createMock(CustomObject::class);
        $this->viewController     = new ViewController(
            $doctrine,
            $modelFactory,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $translator,
            $flashBag,
            $this->requestStack,
            $security,
            $this->formFactory,
            $this->customObjectModel,
            $this->auditLog,
            $this->permissionProvider,
            $this->routeProvider
        );

        $this->addSymfonyDependencies($this->viewController);
    }

    public function testViewActionIfCustomObjectNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('Object not found message')));

        $this->permissionProvider->expects($this->never())
            ->method('canView');

        $this->routeProvider->expects($this->never())
            ->method('buildViewRoute');

        $this->viewController->viewAction(self::OBJECT_ID);
    }

    public function testViewActionIfCustomObjectForbidden(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canView')
            ->will($this->throwException(new ForbiddenException('view')));

        $this->routeProvider->expects($this->never())
            ->method('buildViewRoute');

        $this->expectException(AccessDeniedHttpException::class);

        $this->viewController->viewAction(self::OBJECT_ID);
    }

    public function testViewAction(): void
    {
        $this->customObject->expects($this->once())
            ->method('getDateAdded')
            ->willReturn('2019-01-04 10:20:30');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canView');

        $this->routeProvider->expects($this->once())
            ->method('buildViewRoute')
            ->with(self::OBJECT_ID);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(DateRangeType::class)
            ->willReturn($this->form);

        $this->form
            ->method('get')
            ->willReturnMap(
                [
                    ['date_from', $this->form],
                    ['date_to', $this->form],
                ]
            );

        $this->form
            ->method('getData')
            ->willReturnOnConsecutiveCalls('2019-02-04', '2019-03-04');

        $this->customObjectModel->expects($this->once())
            ->method('getItemsLineChartData')
            ->with(
                $this->callback(function ($dateFrom) {
                    $this->assertSame('2019-02-04', $dateFrom->format('Y-m-d'));

                    return true;
                }),
                $this->callback(function ($dateTo) {
                    $this->assertSame('2019-03-04', $dateTo->format('Y-m-d'));

                    return true;
                }),
                $this->customObject
            );

        $this->auditLog->expects($this->once())
            ->method('getLogForObject')
            ->with('customObject', self::OBJECT_ID, '2019-01-04 10:20:30', 10, 'customObjects');

        $this->viewController->viewAction(self::OBJECT_ID);
    }
}
