<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Mautic\CoreBundle\Service\FlashBag;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\SaveController;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SaveControllerTest extends ControllerTestCase
{
    public const OBJECT_ID = 33;

    public const ITEM_ID = 22;

    public const CONTACT_ID = 11;

    /**
     * @var MockObject|FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var MockObject|CustomItemModel
     */
    private $customItemModel;

    /**
     * @var MockObject|CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var MockObject|FlashBag
     */
    private $flashBag;

    /**
     * @var MockObject|CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var MockObject|CustomItemRouteProvider
     */
    private $routeProvider;

    /**
     * @var MockObject|LockFlashMessageHelper
     */
    private $lockFlashMessageHelper;

    /**
     * @var MockObject|CustomItem
     */
    private $customItem;

    /**
     * @var MockObject|FormInterface
     */
    private $form;

    /**
     * @var SaveController
     */
    private $saveController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory             = $this->createMock(FormFactoryInterface::class);
        $this->customItemModel         = $this->createMock(CustomItemModel::class);
        $this->customObjectModel       = $this->createMock(CustomObjectModel::class);
        $this->flashBag                = $this->createMock(FlashBag::class);
        $this->permissionProvider      = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider           = $this->createMock(CustomItemRouteProvider::class);
        $this->lockFlashMessageHelper  = $this->createMock(LockFlashMessageHelper::class);
        $this->requestStack            = $this->createMock(RequestStack::class);
        $this->request                 = new Request();
        $this->customItem              = $this->createMock(CustomItem::class);
        $this->form                    = $this->createMock(FormInterface::class);
        $this->saveController          = new SaveController(
            $this->requestStack,
            $this->formFactory,
            $this->flashBag,
            $this->customItemModel,
            $this->customObjectModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->lockFlashMessageHelper
        );

        $this->addSymfonyDependencies($this->saveController);

        $saveControllerReflectionObject = new \ReflectionObject($this->saveController);
        $reflectionProperty             = $saveControllerReflectionObject->getProperty('permissionBase');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->saveController, 'somePermissionBase');
        $this->customItemModel->method('save')->willReturn($this->customItem);
    }

    public function testSaveActionIfExistingCustomItemNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testSaveActionIfExistingCustomItemIsForbidden(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->expectException(AccessDeniedHttpException::class);

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testSaveActionForExistingCustomItemWithValidForm(): void
    {
        $this->request->initialize(
            [],
            [
                'custom_item' => [
                    'contact_id' => self::CONTACT_ID,
                ],
            ]
        );

        $this->customItem->expects($this->once())
            ->method('getName')
            ->willReturn('Umpalumpa');

        $this->customItem->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(false);

        $clickable = $this->createMock(ClickableInterface::class);
        $this->form
            ->method('get')
            ->willReturnMap(
                [
                    ['save', $clickable],
                    ['buttons', $this->form],
                ]
            );

        $clickable->expects($this->once())
            ->method('isClicked')
            ->willReturn(true);

        $this->routeProvider->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://edit.item');

        $this->routeProvider->method('buildSaveRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://save.item');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomItemType::class,
                $this->customItem,
                [
                    'action'   => 'https://save.item',
                    'objectId' => self::OBJECT_ID,
                ]
            )
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($this->customItem);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with(
                'mautic.core.notice.updated',
                [
                    '%name%' => 'Umpalumpa',
                    '%url%'  => 'https://edit.item',
                ]
            );

        $this->router->expects($this->once())
            ->method('generate')
            ->willReturn('someRedirectUrl');

        Assert::assertSame(Request::METHOD_GET, $this->request->getMethod());

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testThatSaveActionRedirectToContactViewPageWhenContactIdIsSet(): void
    {
        $this->request->initialize(
            [],
            [
                'custom_item' => [
                    'contact_id' => self::CONTACT_ID,
                ],
            ]
        );

        $this->customItem->expects($this->once())
            ->method('getName')
            ->willReturn('Umpalumpa');

        $this->customItem->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(false);

        $this->routeProvider->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://edit.item');

        $this->routeProvider->method('buildSaveRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://save.item');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomItemType::class,
                $this->customItem,
                [
                    'action'   => 'https://save.item',
                    'objectId' => self::OBJECT_ID,
                ]
            )
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $clickable = $this->createMock(ClickableInterface::class);
        $this->form
            ->method('get')
            ->willReturnMap(
                [
                    ['save', $clickable],
                    ['buttons', $this->form],
                ]
            );

        $clickable->expects($this->once())
            ->method('isClicked')
            ->willReturn(true);

        $this->router->expects($this->once())
            ->method('generate')
            ->willReturn('someRedirectUrl');

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testSaveActionIfNewCustomItemIsForbidden(): void
    {
        $this->request->initialize(
            [],
            [
                'custom_item' => [
                    'contact_id' => self::CONTACT_ID,
                ],
            ]
        );

        $this->customItemModel->expects($this->never())
            ->method('fetchEntity');

        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->will($this->throwException(new ForbiddenException('create')));

        $this->expectException(AccessDeniedHttpException::class);

        $this->saveController->saveAction(self::OBJECT_ID);
    }

    public function testSaveActionForNewCustomItemWithInvalidForm(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('populateCustomFields')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->routeProvider->expects($this->once())
            ->method('buildNewRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('https://create.item');

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with(self::OBJECT_ID, null)
            ->willReturn('https://save.item');

        $this->customItem->method('getCustomObject')
            ->willReturn(new CustomObject());

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomItemType::class,
                $this->customItem,
                [
                    'action'   => 'https://save.item',
                    'objectId' => self::OBJECT_ID,
                ]
            )
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->customItemModel->expects($this->never())
            ->method('save');

        $this->saveController->saveAction(self::OBJECT_ID);
    }

    public function testSaveActionForNewCustomItemWithChildItemWhenInvalidForm(): void
    {
        $this->request->initialize(
            [],
            [
                'custom_item' => [
                    'contact_id' => self::CONTACT_ID,
                ],
            ]
        );

        $this->customItemModel->expects($this->exactly(2))
            ->method('populateCustomFields')
            ->willReturnCallback(
                function () {
                    return func_get_arg(0);
                }
            );

        $this->permissionProvider->expects($this->never())
            ->method('canEdit');

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->routeProvider->method('buildNewRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('https://create.item');

        $this->routeProvider->method('buildSaveRoute')
            ->with(self::OBJECT_ID, null)
            ->willReturn('https://save.item');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn(
                new class() extends CustomObject {
                    public function getId()
                    {
                        return SaveControllerTest::OBJECT_ID;
                    }

                    public function getRelationshipObject(): CustomObject
                    {
                        return new class() extends CustomObject {
                            public function getId()
                            {
                                return 6668;
                            }
                        };
                    }
                }
            );

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomItemType::class,
                $this->callback(
                    function (CustomItem $customItem) {
                        Assert::assertSame(self::OBJECT_ID, $customItem->getCustomObject()->getId());
                        Assert::assertSame(6668, $customItem->getChildCustomItem()->getCustomObject()->getId());

                        return true;
                    }
                ),
                [
                    'action'   => 'https://save.item',
                    'objectId' => self::OBJECT_ID,
                ]
            )
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->customItemModel->expects($this->never())
            ->method('save');

        $this->saveController->saveAction(self::OBJECT_ID);
    }

    public function testSaveActionWhenTheItemIsLocked(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(true);

        $userMock = $this->createMock(User::class);
        $this->userHelper->expects($this->once())
            ->method('getUser')
            ->willReturn($userMock);

        $userMock->expects($this->once())
            ->method('isAdmin')
            ->willReturn(true);

        $this->routeProvider->expects($this->once())
            ->method('buildViewRoute')
            ->with(static::OBJECT_ID, static::ITEM_ID)
            ->willReturn('https://redirect.url');

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testThatUserIsGettingRedirectedWhenWeEditCustomItemAndContactIdIsSpecified(): void
    {
        $this->request->initialize(
            [],
            [
                'custom_item' => [
                    'contact_id' => self::CONTACT_ID,
                ],
            ]
        );

        $this->customItem->expects($this->once())
            ->method('getName')
            ->willReturn('Umpalumpa');

        $this->customItem->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(false);

        $clickable = $this->createMock(ClickableInterface::class);

        $clickable->expects($this->once())
            ->method('isClicked')
            ->willReturn(false);

        $this->routeProvider->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://edit.item');

        $this->routeProvider->method('buildSaveRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://save.item');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomItemType::class,
                $this->customItem,
                [
                    'action'   => 'https://save.item',
                    'objectId' => self::OBJECT_ID,
                ]
            )
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form
            ->method('get')
            ->willReturnMap(
                [
                    ['save', $clickable],
                    ['buttons', $this->form],
                ]
            );

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($this->customItem);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with(
                'mautic.core.notice.updated',
                [
                    '%name%' => 'Umpalumpa',
                    '%url%'  => 'https://edit.item',
                ]
            );

        Assert::assertSame(Request::METHOD_GET, $this->request->getMethod());

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }
}
