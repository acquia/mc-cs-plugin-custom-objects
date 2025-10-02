<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomField;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FormControllerTest extends AbstractFieldControllerTest
{
    /**
     * @var MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var MockObject|CorePermissions
     */
    protected $security;

    /**
     * @var MockObject|FormFactory
     */
    private $formFactory;

    /**
     * @var MockObject|CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var MockObject|CustomFieldFactory
     */
    private $customFieldFactory;

    /**
     * @var MockObject|CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var MockObject|CustomFieldRouteProvider
     */
    private $fieldRouteProvider;

    /**
     * @var MockObject|CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var MockObject|CustomObjectRouteProvider
     */
    private $objectRouteProvider;

    /**
     * @var MockObject|FormInterface
     */
    private $form;

    /**
     * @var MockObject|FormController
     */
    private $formController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory          = $this->createMock(FormFactory::class);
        $this->customFieldModel     = $this->createMock(CustomFieldModel::class);
        $this->customFieldFactory   = $this->createMock(CustomFieldFactory::class);
        $this->permissionProvider   = $this->createMock(CustomFieldPermissionProvider::class);
        $this->fieldRouteProvider   = $this->createMock(CustomFieldRouteProvider::class);
        $this->customObjectModel    = $this->createMock(CustomObjectModel::class);
        $this->objectRouteProvider  = $this->createMock(CustomObjectRouteProvider::class);
        $this->form                 = $this->createMock(FormInterface::class);
    }

    public function testRenderFormIfCustomFieldNotFoundFormController(): void
    {
        $objectId     = 1;
        $fieldId      = 2;
        $fieldType    = 'text';
        $panelId      = null;
        $panelCount   = null;

        $this->createFormController(
            $objectId,
            $fieldId,
            $fieldType,
            $panelId,
            $panelCount
        );

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException('not found message')));

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'not found message',
                [
                    '%url%' => null,
                ]
            )
            ->willReturn('not found message');

        $this->formController->renderFormAction(
            $this->formFactory,
            $this->customFieldModel,
            $this->customFieldFactory,
            $this->permissionProvider,
            $this->fieldRouteProvider,
            $this->customObjectModel,
            $this->objectRouteProvider
        );
    }

    public function testRenderFormIfCustomFieldAccessDenied(): void
    {
        $objectId     = 1;
        $fieldId      = 2;
        $fieldType    = 'text';
        $panelId      = null;
        $panelCount   = null;

        $this->createFormController(
            $objectId,
            $fieldId,
            $fieldType,
            $panelId,
            $panelCount
        );

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->with($fieldId)
            ->willReturn(new CustomField());

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('forbidden message')));

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->renderFormAction(
            $this->formFactory,
            $this->customFieldModel,
            $this->customFieldFactory,
            $this->permissionProvider,
            $this->fieldRouteProvider,
            $this->customObjectModel,
            $this->objectRouteProvider
        );
    }

    public function testRenderFormActionEditField(): void
    {
        $objectId     = 1;
        $fieldId      = 2;
        $fieldType    = 'text';
        $panelId      = null;
        $panelCount   = null;

        $this->createFormController(
            $objectId,
            $fieldId,
            $fieldType,
            $panelId,
            $panelCount
        );

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
            ->with(
                $fieldType,
                $fieldId,
                $customObject->getId(),
                $panelCount,
                $panelId
            )
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

        $this->formController->renderFormAction(
            $this->formFactory,
            $this->customFieldModel,
            $this->customFieldFactory,
            $this->permissionProvider,
            $this->fieldRouteProvider,
            $this->customObjectModel,
            $this->objectRouteProvider
        );
    }

    public function testRenderFormActionCreateField(): void
    {
        $objectId   = null;
        $fieldId    = null;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $this->createFormController(
            $objectId,
            $fieldId,
            $fieldType,
            $panelId,
            $panelCount
        );

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

        $this->formController->renderFormAction(
            $this->formFactory,
            $this->customFieldModel,
            $this->customFieldFactory,
            $this->permissionProvider,
            $this->fieldRouteProvider,
            $this->customObjectModel,
            $this->objectRouteProvider
        );
    }

    private function createFormController(
        ?int $objectId,
        ?int $fieldId,
        string $fieldType,
        ?int $panelId = null,
        ?int $panelCount = null
    ): void {
        $this->requestStack  = $this->createRequestStackMock(
            $objectId,
            $fieldId,
            $fieldType,
            $panelId,
            $panelCount
        );

        $this->formController = new FormController(
            $this->managerRegistry,
            $this->mauticFactory,
            $this->modelFactory,
            $this->userHelper,
            $this->coreParametersHelper,
            $this->dispatcher,
            $this->translator,
            $this->flashBag,
            $this->requestStack,
            $this->security
        );

        $this->addSymfonyDependencies($this->formController);

        $this->container->get('http_kernel')->expects($this->any())
            ->method('handle')
            ->willreturn(null);
    }
}
