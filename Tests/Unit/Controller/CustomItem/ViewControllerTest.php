<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Mautic\CoreBundle\Form\Type\DateRangeType;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ViewController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
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

    private const ITEM_ID = 22;

    private $customItemModel;
    private $customItemXrefContactModel;
    private $auditLog;
    private $permissionProvider;
    private $routeProvider;
    private $formFactory;
    private $form;
    private $customItem;

    /**
     * @var ViewController
     */
    private $viewController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel            = $this->createMock(CustomItemModel::class);
        $this->customItemXrefContactModel = $this->createMock(CustomItemXrefContactModel::class);
        $this->auditLog                   = $this->createMock(AuditLogModel::class);
        $this->permissionProvider         = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider              = $this->createMock(CustomItemRouteProvider::class);
        $this->formFactory                = $this->createMock(FormFactoryInterface::class);
        $this->form                       = $this->createMock(FormInterface::class);
        $this->customItem                 = $this->createMock(CustomItem::class);
        $this->requestStack               = $this->createMock(RequestStack::class);
        $this->request                    = $this->createMock(Request::class);
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->translator                 = $this->createMock(Translator::class);

        $this->viewController             = new ViewController();
        $this->viewController->setTranslator($this->translator);
        $this->viewController->setSecurity($this->security);

        $this->addSymfonyDependencies($this->viewController);
    }

    public function testViewActionIfCustomItemNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('Item not found message')));

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
            $this->customItemModel,
            $this->customItemXrefContactModel,
            $this->auditLog,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID,
            self::ITEM_ID
        );
    }

    public function testViewActionIfCustomItemForbidden(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

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
            $this->customItemModel,
            $this->customItemXrefContactModel,
            $this->auditLog,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID,
            self::ITEM_ID
        );
    }

    public function testViewAction(): void
    {
        $this->customItem->expects($this->once())
            ->method('getDateAdded')
            ->willReturn('2019-01-04 10:20:30');

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canView');

        $this->routeProvider->expects($this->once())
            ->method('buildViewRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID);

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

        $this->customItemXrefContactModel->expects($this->once())
            ->method('getLinksLineChartData')
            ->with(
                $this->callback(function ($dateFrom) {
                    $this->assertSame('2019-02-04', $dateFrom->format('Y-m-d'));

                    return true;
                }),
                $this->callback(function ($dateTo) {
                    $this->assertSame('2019-03-04', $dateTo->format('Y-m-d'));

                    return true;
                }),
                $this->customItem
            );

        $this->auditLog->expects($this->once())
            ->method('getLogForObject')
            ->with('customItem', self::ITEM_ID, '2019-01-04 10:20:30', 10, 'customObjects');

        $this->viewController->viewAction(
            $this->requestStack,
            $this->formFactory,
            $this->customItemModel,
            $this->customItemXrefContactModel,
            $this->auditLog,
            $this->permissionProvider,
            $this->routeProvider,
            self::OBJECT_ID,
            self::ITEM_ID
        );
    }
}
