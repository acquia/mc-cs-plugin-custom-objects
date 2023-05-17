<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ViewController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
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

        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->auditLog           = $this->createMock(AuditLogModel::class);
        $this->permissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomObjectRouteProvider::class);
        $this->requestStack       = $this->createMock(RequestStack::class);
        $this->request            = $this->createMock(Request::class);
        $this->formFactory        = $this->createMock(FormFactoryInterface::class);
        $this->form               = $this->createMock(FormInterface::class);
        $this->customObject       = $this->createMock(CustomObject::class);

        $this->translator         = $this->createMock(Translator::class);

        $this->viewController     = new ViewController();
        $this->viewController->setTranslator($this->translator);
        $this->viewController->setSecurity($this->security);

        $this->addSymfonyDependencies($this->viewController);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
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

        $post  = $this->createMock(ParameterBag::class);
        $this->request->request = $post;
        $post->expects($this->once())
            ->method('all')
            ->willReturn([]);

        $this->viewController->viewAction(
            $this->requestStack,
            $this->formFactory,
            $this->customObjectModel,
            $this->auditLog,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID
        );
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

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->expectException(AccessDeniedHttpException::class);

        $this->viewController->viewAction(
            $this->requestStack,
            $this->formFactory,
            $this->customObjectModel,
            $this->auditLog,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID
        );
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

        $this->viewController->viewAction(
            $this->requestStack,
            $this->formFactory,
            $this->customObjectModel,
            $this->auditLog,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID
        );
    }
}
