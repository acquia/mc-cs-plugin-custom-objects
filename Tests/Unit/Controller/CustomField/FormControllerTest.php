<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomField;

use MauticPlugin\CustomObjectsBundle\Controller\CustomField\FormController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Factory\ModelFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Doctrine\Persistence\ManagerRegistry;

class FormControllerTest extends AbstractFieldControllerTest
{
    private $formFactory;
    private $customFieldModel;
    private $customFieldFactory;
    private $permissionProvider;
    private $fieldRouteProvider;
    private $customObjectModel;
    private $objectRouteProvider;
    private $form;
    private $formController;

    private $registry;
    private $coreParametersHelper;
    private $translator;
    private $flashBag;
    private $security;
    private $modelFactory;
    private $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry              = $this->createMock(ManagerRegistry::class);
        $this->coreParametersHelper  = $this->createMock(CoreParametersHelper::class);
        $this->translator            = $this->createMock(Translator::class);
        $this->flashBag              = $this->createMock(FlashBag::class);
        $this->requestStack          = $this->createMock(RequestStack::class);
        $this->security              = $this->createMock(CorePermissions::class);
        $this->customFieldFactory    = $this->createMock(CustomFieldFactory::class);
        $this->customObjectModel     = $this->createMock(CustomObjectModel::class);
        $this->customFieldModel      = $this->createMock(CustomFieldModel::class);
        $this->permissionProvider    = $this->createMock(CustomFieldPermissionProvider::class);
        $this->fieldRouteProvider    = $this->createMock(CustomFieldRouteProvider::class);
        $this->objectRouteProvider   = $this->createMock(CustomObjectRouteProvider::class);
        $this->form                  = $this->createMock(FormInterface::class);
        $this->modelFactory          = $this->createMock(ModelFactory::class);
        $this->userHelper            = $this->createMock(UserHelper::class);
        $this->dispatcher            = $this->createMock(EventDispatcherInterface::class);
        $this->formFactory           = $this->createMock(FormFactory::class);
        $this->formController        = new FormController(
            $this->formFactory,
            $this->registry,
            $this->modelFactory,
            $this->userHelper,
            $this->coreParametersHelper,
            $this->dispatcher,
            $this->translator,
            $this->flashBag,
            $this->requestStack,
            $this->security,
            $this->objectRouteProvider,
            $this->fieldRouteProvider,
            $this->customFieldFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider
        );

        $this->addSymfonyDependencies($this->formController);
    }

    public function testRenderFormIfCustomFieldNotFound(): void
    {
        $objectId   = 1;
        $fieldId    = 2;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $request = $this->createRequestMock($objectId, $fieldId, $fieldType, $panelId, $panelCount);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('not found message')));

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->formController->renderFormAction($request);
    }

    public function testRenderFormIfCustomFieldAccessDenied(): void
    {
        $objectId   = 1;
        $fieldId    = 2;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $request = $this->createRequestMock($objectId, $fieldId, $fieldType, $panelId, $panelCount);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->with($fieldId)
            ->willReturn(new CustomField());

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('forbidden message')));

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->renderFormAction($request);
    }

    public function testRenderFormActionEditField(): void
    {
        $objectId   = 1;
        $fieldId    = 2;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $request = $this->createRequestMock($objectId, $fieldId, $fieldType, $panelId, $panelCount);

        $customObject = new CustomObject();
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with($objectId)
            ->willReturn($customObject);

        $customField = new CustomField();
        $customField->setId($fieldId);
        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->with($fieldId)
            ->willReturn($customField);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($customField);

        $route = 'route';
        $this->fieldRouteProvider->expects($this->once())
            ->method('buildFormRoute')
            ->with($customField->getId())
            ->willReturn($route);

        $action = 'action';
        $this->fieldRouteProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with($fieldType, $fieldId, $customObject->getId(), $panelCount, $panelId)
            ->willReturn($action);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomFieldType::class, $customField, ['action' => $action])
            ->willReturn($this->form);

        $returnUrl = 'returnUrl';
        $this->objectRouteProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with($customObject->getId())
            ->willReturn($returnUrl);

        $view       = 'view';
        $this->form->expects($this->once())
            ->method('createView')
            ->willReturn($view);

        $this->formController->renderFormAction($request);
    }

    public function testRenderFormActionCreateField(): void
    {
        $objectId   = null;
        $fieldId    = null;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $request = $this->createRequestMock($objectId, $fieldId, $fieldType, $panelId, $panelCount);

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $customField = new CustomField();

        $customObject = new CustomObject();
        $this->customFieldFactory->expects($this->once())
            ->method('create')
            ->with($fieldType, $customObject)
            ->willReturn($customField);

        $route = 'route';
        $this->fieldRouteProvider->expects($this->once())
            ->method('buildFormRoute')
            ->with($customField->getId())
            ->willReturn($route);

        $action = 'action';
        $this->fieldRouteProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with($fieldType, $fieldId, $customObject->getId(), $panelCount, $panelId)
            ->willReturn($action);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomFieldType::class, $customField, ['action' => $action])
            ->willReturn($this->form);

        $returnUrl = 'returnUrl';
        $this->objectRouteProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(null)
            ->willReturn($returnUrl);

        $view       = 'view';
        $this->form->expects($this->once())
            ->method('createView')
            ->willReturn($view);

        $this->formController->renderFormAction($request);
    }
}
