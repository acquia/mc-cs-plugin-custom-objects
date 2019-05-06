<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\SaveController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormFactoryInterface;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ClickableInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;

class SaveControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private $formFactory;
    private $customObjectModel;
    private $customFieldModel;
    private $customFieldTypeProvider;
    private $paramsToStringTransformer;
    private $optionsToStringTransformer;
    private $flashBag;
    private $permissionProvider;
    private $routeProvider;
    private $lockFlashMessageHelper;
    private $customObject;
    private $form;

    /**
     * @var SaveController
     */
    private $saveController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory                = $this->createMock(FormFactoryInterface::class);
        $this->customObjectModel          = $this->createMock(CustomObjectModel::class);
        $this->customFieldModel           = $this->createMock(CustomFieldModel::class);
        $this->flashBag                   = $this->createMock(FlashBag::class);
        $this->permissionProvider         = $this->createMock(CustomObjectPermissionProvider::class);
        $this->routeProvider              = $this->createMock(CustomObjectRouteProvider::class);
        $this->requestStack               = $this->createMock(RequestStack::class);
        $this->customFieldTypeProvider    = $this->createMock(CustomFieldTypeProvider::class);
        $this->paramsToStringTransformer  = $this->createMock(ParamsToStringTransformer::class);
        $this->optionsToStringTransformer = $this->createMock(OptionsToStringTransformer::class);
        $this->lockFlashMessageHelper     = $this->createMock(LockFlashMessageHelper::class);
        $this->request                    = $this->createMock(Request::class);
        $this->customObject               = $this->createMock(CustomObject::class);
        $this->form                       = $this->createMock(FormInterface::class);
        $this->saveController             = new SaveController(
            $this->requestStack,
            $this->flashBag,
            $this->formFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customFieldTypeProvider,
            $this->paramsToStringTransformer,
            $this->optionsToStringTransformer,
            $this->lockFlashMessageHelper
        );

        $this->addSymfonyDependencies($this->saveController);
    }

    public function testSaveActionIfExistingCustomObjectNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->saveController->saveAction(self::OBJECT_ID);
    }

    public function testSaveActionIfExistingCustomObjectIsForbidden(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->expectException(AccessDeniedHttpException::class);

        $this->saveController->saveAction(self::OBJECT_ID);
    }

    public function testSaveActionForExistingCustomObjectWithValidForm(): void
    {
        $this->customObject->expects($this->once())
            ->method('getName')
            ->willReturn('Umpalumpa');

        $this->customObject->expects($this->once())
            ->method('getId')
            ->willReturn(self::OBJECT_ID);

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customObject);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customObject)
            ->willReturn(false);

        $this->routeProvider->expects($this->exactly(2))
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('https://edit.object');

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('https://save.object');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomObjectType::class,
                $this->customObject,
                ['action' => 'https://save.object']
            )
            ->willReturn($this->form);

        $this->form->expects($this->at(0))
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->at(1))
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->at(2))
            ->method('get')
            ->with('buttons')
            ->willReturnSelf();

        $this->form->expects($this->at(3))
            ->method('get')
            ->with('save')
            ->willReturn($this->createMock(ClickableInterface::class));

        $this->customObjectModel->expects($this->once())
            ->method('save')
            ->with($this->customObject);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with(
                'mautic.core.notice.updated',
                [
                    '%name%' => 'Umpalumpa',
                    '%url%'  => 'https://edit.object',
                ]
            );

        $this->customFieldModel->expects($this->never())
            ->method('fetchCustomFieldsForObject');

        $this->request->expects($this->once())
            ->method('get')
            ->with('custom_object')
            ->willReturn([]);

        $this->saveController->saveAction(self::OBJECT_ID);
    }

    public function testSaveActionForNewCustomObjectWithInvalidForm(): void
    {
        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->routeProvider->expects($this->once())
            ->method('buildNewRoute')
            ->with()
            ->willReturn('https://create.object');

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with(null)
            ->willReturn('https://save.object');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomObjectType::class,
                $this->isInstanceOf(CustomObject::class),
                ['action' => 'https://save.object']
            )
            ->willReturn($this->form);

        $this->form->expects($this->at(0))
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->at(1))
            ->method('isValid')
            ->willReturn(false);

        $this->customObjectModel->expects($this->never())
            ->method('save');

        $this->customFieldModel->expects($this->once())
            ->method('fetchCustomFieldsForObject')
            ->with($this->isInstanceOf(CustomObject::class));

        $this->customFieldTypeProvider->expects($this->once())
            ->method('getTypes');

        $this->saveController->saveAction();
    }
}
