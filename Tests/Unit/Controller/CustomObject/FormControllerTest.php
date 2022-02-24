<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\FormController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FormControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $customObjectModel;
    private $customFieldModel;
    private $formFactory;
    private $permissionProvider;
    private $routeProvider;
    private $customFieldTypeProvider;
    private $lockFlashMessageHelper;
    private $customObject;
    private $form;

    /**
     * @var FormController
     */
    private $formController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel       = $this->createMock(CustomObjectModel::class);
        $this->customFieldModel        = $this->createMock(CustomFieldModel::class);
        $this->formFactory             = $this->createMock(FormFactory::class);
        $this->permissionProvider      = $this->createMock(CustomObjectPermissionProvider::class);
        $this->routeProvider           = $this->createMock(CustomObjectRouteProvider::class);
        $this->customFieldTypeProvider = $this->createMock(CustomFieldTypeProvider::class);
        $this->lockFlashMessageHelper  = $this->createMock(LockFlashMessageHelper::class);
        $this->request                 = $this->createMock(Request::class);
        $this->customObject            = $this->createMock(CustomObject::class);
        $this->form                    = $this->createMock(FormInterface::class);
        $this->formController          = new FormController(
            $this->formFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customFieldTypeProvider,
            $this->lockFlashMessageHelper
        );

        $this->addSymfonyDependencies($this->formController);

        $this->customObject->method('getId')->willReturn(self::OBJECT_ID);
        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
    }

    public function testNewActionIfForbidden(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->will($this->throwException(new ForbiddenException('create')));

        $this->routeProvider->expects($this->never())
            ->method('buildNewRoute');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->newAction();
    }

    public function testNewAction(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->routeProvider->expects($this->once())
            ->method('buildNewRoute')
            ->with();

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomObjectType::class,
                $this->isInstanceOf(CustomObject::class),
                ['action' => 'https://list.items']
            )
            ->willReturn($this->form);

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->willReturn('https://list.items');

        $this->formController->newAction();
    }

    public function testEditActionIfCustomObjectNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->will($this->throwException(new NotFoundException('Object not found message')));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRoute');

        $this->formController->editAction(self::OBJECT_ID);
    }

    public function testEditActionIfCustomObjectForbidden(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRoute');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->editAction(self::OBJECT_ID);
    }

    public function testEditAction(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customObject);

        $this->routeProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomObjectType::class,
                $this->customObject,
                ['action' => 'https://list.items']
            )
            ->willReturn($this->form);

        $this->customObjectModel->expects($this->once())
            ->method('isLocked')
            ->willReturn(false);

        $this->customObjectModel->expects($this->once())
            ->method('lockEntity');

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('https://list.items');

        $this->formController->editAction(self::OBJECT_ID);
    }

    public function testCloneActionIfCustomObjectNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->will($this->throwException(new NotFoundException('Object not found message')));

        $this->routeProvider->expects($this->never())
            ->method('buildCloneRoute');

        $this->formController->cloneAction(self::OBJECT_ID);
    }

    public function testCloneActionIfCustomObjectForbidden(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canClone')
            ->will($this->throwException(new ForbiddenException('clone')));

        $this->routeProvider->expects($this->never())
            ->method('buildCloneRoute');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->cloneAction(self::OBJECT_ID);
    }

    public function testCloneAction(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canClone');

        $this->routeProvider->expects($this->once())
            ->method('buildCloneRoute')
            ->with(self::OBJECT_ID);

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomObjectType::class,
                $this->customObject,
                ['action' => 'https://list.items']
            )
            ->willReturn($this->form);

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->willReturn('https://list.items');

        $this->formController->cloneAction(self::OBJECT_ID);
    }
}
