<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomObject;

use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use MauticPlugin\CustomObjectsBundle\Controller\CustomObject\SaveController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomObjectType;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

        $this->translator                 = $this->createMock(Translator::class);

        $this->saveController             = new SaveController($this->security, $this->userHelper, $this->requestStack);
        $this->saveController->setTranslator($this->translator);

        $this->addSymfonyDependencies($this->saveController);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
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

        $post  = $this->createMock(ParameterBag::class);
        $this->request->request = $post;
        $post->expects($this->once())
            ->method('all')
            ->willReturn([]);

        $this->saveController->saveAction(
            $this->flashBag,
            $this->formFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customFieldTypeProvider,
            $this->paramsToStringTransformer,
            $this->optionsToStringTransformer,
            $this->lockFlashMessageHelper,
            self::OBJECT_ID
        );
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

        $this->saveController->saveAction(
            $this->flashBag,
            $this->formFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customFieldTypeProvider,
            $this->paramsToStringTransformer,
            $this->optionsToStringTransformer,
            $this->lockFlashMessageHelper,
            self::OBJECT_ID
        );
    }

    public function testSaveActionForExistingCustomObjectWithValidFormClickingApply(): void
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

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form
            ->method('get')
            ->willReturnMap(
                [
                    ['buttons', $this->form],
                    ['save', $this->createMock(ClickableInterface::class)],
                ]
            );

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

        $this->saveController->saveAction(
            $this->flashBag,
            $this->formFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customFieldTypeProvider,
            $this->paramsToStringTransformer,
            $this->optionsToStringTransformer,
            $this->lockFlashMessageHelper,
            self::OBJECT_ID
        );
    }

    public function testSaveActionForExistingCustomObjectWithValidFormClickingSaveAndAjax(): void
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

        $this->routeProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('https://edit.object');

        $this->routeProvider->expects($this->once())
            ->method('buildViewRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('https://view.object');

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

        $this->form->expects($this->once())
            ->method('submit')
            ->with([]);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $click = $this->createMock(ClickableInterface::class);

        $this->form
            ->method('get')
            ->willReturnMap(
                [
                    ['buttons', $this->form],
                    ['save', $click],
                ]
            );

        $click->expects($this->once())
            ->method('isClicked')
            ->willReturn(true);

        $this->customObjectModel->expects($this->once())
            ->method('unlockEntity')
            ->with($this->customObject);

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

        /** @var JsonResponse $jsonResponse */
        $jsonResponse = $this->saveController->saveAction(
            $this->flashBag,
            $this->formFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customFieldTypeProvider,
            $this->paramsToStringTransformer,
            $this->optionsToStringTransformer,
            $this->lockFlashMessageHelper,
            self::OBJECT_ID
        );

        $this->assertMatchesRegularExpression('/Redirecting to https:\/\/view.object/', $jsonResponse->getContent());
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

        $this->request
            ->method('get')
            ->willReturnMap(
                [
                    ['custom_object', null, []],
                ]
            );

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->customObjectModel->expects($this->never())
            ->method('save');

        $this->customFieldModel->expects($this->once())
            ->method('fetchCustomFieldsForObject')
            ->with($this->isInstanceOf(CustomObject::class));

        $this->customFieldTypeProvider->expects($this->once())
            ->method('getTypes');

        $this->saveController->saveAction(
            $this->flashBag,
            $this->formFactory,
            $this->customObjectModel,
            $this->customFieldModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->customFieldTypeProvider,
            $this->paramsToStringTransformer,
            $this->optionsToStringTransformer,
            $this->lockFlashMessageHelper,
        );
    }
}
