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

namespace MauticPlugin\CustomObjectsBundle\Tests\Controller\CustomObject;

use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ViewController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Tests\Controller\ControllerDependenciesTrait;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Mautic\CoreBundle\Form\Type\DateRangeType;

class ViewControllerTest extends \PHPUnit_Framework_TestCase
{
    use ControllerDependenciesTrait;

    private const OBJECT_ID = 33;

    private $customObjectModel;
    private $auditLog;
    private $permissionProvider;
    private $routeProvider;
    private $requestStack;
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
        $this->formFactory        = $this->createMock(FormFactoryInterface::class);
        $this->form               = $this->createMock(FormInterface::class);
        $this->customObject       = $this->createMock(CustomObject::class);
        $this->viewController     = new ViewController(
            $this->requestStack,
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

        $this->form->expects($this->at(0))
            ->method('get')
            ->with('date_from')
            ->willReturnSelf();

        $this->form->expects($this->at(1))
            ->method('getData')
            ->willReturn('2019-02-04');

        $this->form->expects($this->at(2))
            ->method('get')
            ->with('date_to')
            ->willReturnSelf();

        $this->form->expects($this->at(3))
            ->method('getData')
            ->willReturn('2019-03-04');

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
