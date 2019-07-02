<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomField;

use MauticPlugin\CustomObjectsBundle\Controller\CustomField\FormController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;

class FormControllerTest extends ControllerTestCase
{
    private $formFactory;
    private $customFieldModel;
    private $customFieldFactory;
    private $permissionProvider;
    private $fieldRouteProvider;
    private $customObjectModel;
    private $objectRouteProvider;
    private $formController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory         = $this->createMock(FormFactory::class);
        $this->customFieldModel    = $this->createMock(CustomFieldModel::class);
        $this->customFieldFactory  = $this->createMock(CustomFieldFactory::class);
        $this->permissionProvider  = $this->createMock(CustomFieldPermissionProvider::class);
        $this->fieldRouteProvider  = $this->createMock(CustomFieldRouteProvider::class);
        $this->customObjectModel   = $this->createMock(CustomObjectModel::class);
        $this->objectRouteProvider = $this->createMock(CustomObjectRouteProvider::class);

        $this->formController = new FormController(
            $this->formFactory,
            $this->customFieldModel,
            $this->customFieldFactory,
            $this->permissionProvider,
            $this->fieldRouteProvider,
            $this->customObjectModel,
            $this->objectRouteProvider
        );

        $this->addSymfonyDependencies($this->formController);
    }

    public function testRenderFormActionEditField(): void
    {
        $objectId   = 1;
        $fieldId    = 2;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $request = $this->createMock(Request::class);
        $request->expects($this->at(0))
            ->method('get')
            ->with('objectId')
            ->willReturn($objectId);
        $request->expects($this->at(1))
            ->method('get')
            ->with('fieldId')
            ->willReturn($fieldId);
        $request->expects($this->at(2))
            ->method('get')
            ->with('fieldType')
            ->willReturn($fieldType);
        $request->expects($this->at(3))
            ->method('get')
            ->with('panelId')
            ->willReturn($panelId);
        $request->expects($this->at(4))
            ->method('get')
            ->with('panelCount')
            ->willReturn($panelCount);

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

        $view = 'view';
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($view);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomFieldType::class, $customField, ['action' => $action])
            ->willReturn($form);

        $returnUrl = 'returnUrl';
        $this->objectRouteProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with($customObject->getId())
            ->willReturn($returnUrl);

        $this->formController->renderFormAction($request);
    }

    public function testRenderFormActionCreateField()
    {
        $objectId   = null;
        $fieldId    = null;
        $fieldType  = 'text';
        $panelId    = null;
        $panelCount = null;

        $request = $this->createMock(Request::class);
        $request->expects($this->at(0))
            ->method('get')
            ->with('objectId')
            ->willReturn($objectId);
        $request->expects($this->at(1))
            ->method('get')
            ->with('fieldId')
            ->willReturn($fieldId);
        $request->expects($this->at(2))
            ->method('get')
            ->with('fieldType')
            ->willReturn($fieldType);
        $request->expects($this->at(3))
            ->method('get')
            ->with('panelId')
            ->willReturn($panelId);
        $request->expects($this->at(4))
            ->method('get')
            ->with('panelCount')
            ->willReturn($panelCount);

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $customField = new CustomField();

        $customObject = new CustomObject();
        $this->customFieldFactory->expects($this->once())
            ->method('create')
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

        $view = 'view';
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($view);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomFieldType::class, $customField, ['action' => $action])
            ->willReturn($form);

        $returnUrl = 'returnUrl';
        $this->objectRouteProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(null)
            ->willReturn($returnUrl);

        $this->formController->renderFormAction($request);
    }
}
