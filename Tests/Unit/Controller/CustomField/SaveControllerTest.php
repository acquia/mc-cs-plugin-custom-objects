<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomField;

use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Controller\CustomField\SaveController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class SaveControllerTest extends AbstractFieldControllerTest
{
    private $formFactory;
    private $translator;
    private $customFieldModel;
    private $customFieldFactory;
    private $permissionProvider;
    private $fieldRouteProvider;
    private $customObjectModel;
    private $form;
    private $saveController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory        = $this->createMock(FormFactory::class);
        $this->translator         = $this->createMock(TranslatorInterface::class);
        $this->customFieldModel   = $this->createMock(CustomFieldModel::class);
        $this->customFieldFactory = $this->createMock(CustomFieldFactory::class);
        $this->permissionProvider = $this->createMock(CustomFieldPermissionProvider::class);
        $this->fieldRouteProvider = $this->createMock(CustomFieldRouteProvider::class);
        $this->customObjectModel  = $this->createMock(CustomObjectModel::class);
        $this->form               = $this->createMock(FormInterface::class);

        $this->saveController = new SaveController(
            $this->formFactory,
            $this->translator,
            $this->customFieldModel,
            $this->customFieldFactory,
            $this->permissionProvider,
            $this->fieldRouteProvider,
            $this->customObjectModel
        );

        $this->addSymfonyDependencies($this->saveController);
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

        $this->saveController->saveAction($request);
    }

    public function testRenderFormIfCustomFieldAccessDenied(): void
    {
        $objectId   = null; // Cover creating CO too
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

        $this->saveController->saveAction($request);
    }

    public function testSaveActionEdit(): void
    {
        $objectId   = 1;
        $fieldId    = 2;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $customObject = $this->createMock(CustomObject::class);
        $customObject->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);

        $customField = $this->createMock(CustomField::class);

        $mapExtras = [
            ['custom_field', null, []],
            ['custom_field', null, []],
            ['panelId', null, $panelCount],
        ];
        $request = $this->createRequestMock($objectId, $fieldId, $fieldType, $panelId, $panelCount, $mapExtras);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with($objectId)
            ->willReturn($customObject);

        $this->customFieldModel->expects($this->once())
            ->method('fetchEntity')
            ->with($fieldId)
            ->willReturn($customField);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($customField);

        $action = 'action';
        $this->fieldRouteProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with($fieldType, $fieldId, $objectId, $panelCount, $panelId)
            ->willReturn($action);

        $this->formFactory
            ->method('create')
            ->willReturnMap(
                [
                    [CustomFieldType::class, $customField, ['action' => $action], $this->form],
                    [CustomObjectType::class, $customObject, [], $this->form],
                ]
            );

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($customField);

        $this->customFieldModel->expects($this->once())
            ->method('setAlias')
            ->with($customField);

        $customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection());

        $this->customFieldModel->expects($this->once())
            ->method('setAlias');

        $customObject->expects($this->once())
            ->method('setCustomFields');

        $this->saveController->saveAction($request);
    }

    public function testSaveActionCreate(): void
    {
        $objectId   = 1;
        $fieldId    = null;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $customObject = $this->createMock(CustomObject::class);
        $customObject->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);

        $customField = $this->createMock(CustomField::class);

        $mapExtras = [
            ['custom_field', null, []],
            ['custom_field', null, []],
            ['panelId', null, $panelCount],
            ['panelCount', null, $panelCount],
            ['custom_field', null, []],
        ];
        $request = $this->createRequestMock($objectId, $fieldId, $fieldType, $panelId, $panelCount, $mapExtras);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with($objectId)
            ->willReturn($customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->with();

        $this->customFieldFactory->expects($this->once())
            ->method('create')
            ->with($fieldType, $customObject)
            ->willReturn($customField);

        $action = 'action';
        $this->fieldRouteProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with($fieldType, $fieldId, $objectId, $panelCount, $panelId)
            ->willReturn($action);

        $this->formFactory
            ->method('create')
            ->willReturnMap(
                [
                    [CustomFieldType::class, $customField, ['action' => $action], $this->form],
                    [CustomObjectType::class, $customObject, [], $this->form],
                ]
            );

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($customField);

        $customField->expects($this->once())
            ->method('setOrder')
            ->with(0);

        $customField->expects($this->once())
            ->method('getOptions')
            ->willReturn(new ArrayCollection());

        $customObject->expects($this->once())
            ->method('setCustomFields');

        $this->saveController->saveAction($request);
    }

    public function testInvalidPost(): void
    {
        $objectId   = 1;
        $fieldId    = null;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $customObject = $this->createMock(CustomObject::class);
        $customObject->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);

        $customField = $this->createMock(CustomField::class);

        $mapExtras = [
            ['custom_field', null, []],
            ['custom_field', null, []],
        ];
        $request = $this->createRequestMock($objectId, $fieldId, $fieldType, $panelId, $panelCount, $mapExtras);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with($objectId)
            ->willReturn($customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->with();

        $this->customFieldFactory->expects($this->once())
            ->method('create')
            ->with($fieldType, $customObject)
            ->willReturn($customField);

        $action = 'action';
        $this->fieldRouteProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with($fieldType, $fieldId, $objectId, $panelCount, $panelId)
            ->willReturn($action);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomFieldType::class, $customField, ['action' => $action])
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->saveController->saveAction($request);
    }
}
