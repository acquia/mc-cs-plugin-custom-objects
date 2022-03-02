<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

use Mautic\UserBundle\Entity\User;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController;
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
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FormControllerTest extends ControllerTestCase
{
    public const OBJECT_ID = 33;

    public const ITEM_ID = 22;

    public const CONTACT_ID = 11;

    /**
     * @var MockObject|CustomItemModel
     */
    private $customItemModel;

    /**
     * @var MockObject|CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var MockObject|FormFactory
     */
    private $formFactory;

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
     * @var MockObject|CustomObject
     */
    private $customObject;

    /**
     * @var MockObject|CustomItem
     */
    private $customItem;

    /**
     * @var MockObject|FormInterface
     */
    private $form;

    /**
     * @var FormController
     */
    private $formController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel         = $this->createMock(CustomItemModel::class);
        $this->customObjectModel       = $this->createMock(CustomObjectModel::class);
        $this->formFactory             = $this->createMock(FormFactory::class);
        $this->permissionProvider      = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider           = $this->createMock(CustomItemRouteProvider::class);
        $this->lockFlashMessageHelper  = $this->createMock(LockFlashMessageHelper::class);
        $this->request                 = $this->createMock(Request::class);
        $this->customObject            = $this->createMock(CustomObject::class);
        $this->customItem              = $this->createMock(CustomItem::class);
        $this->form                    = $this->createMock(FormInterface::class);
        $this->formController          = new FormController(
            $this->formFactory,
            $this->customObjectModel,
            $this->customItemModel,
            $this->permissionProvider,
            $this->routeProvider,
            $this->lockFlashMessageHelper
        );

        $this->addSymfonyDependencies($this->formController);

        $this->customObject->method('getId')->willReturn(self::OBJECT_ID);
        $this->customItem->method('getId')->willReturn(self::ITEM_ID);
        $this->customItem->method('getCustomObject')->willReturn($this->customObject);
        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
        $formControllerReflectionObject = new \ReflectionObject($this->formController);
        $reflectionProperty             = $formControllerReflectionObject->getProperty('permissionBase');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->formController, 'somePermissionBase');
    }

    public function testNewActionIfForbidden(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->will($this->throwException(new ForbiddenException('create')));

        $this->customObjectModel->expects($this->never())
            ->method('fetchEntity');

        $this->customItemModel->expects($this->never())
            ->method('fetchEntity');

        $this->routeProvider->expects($this->never())
            ->method('buildNewRoute');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->newAction(self::OBJECT_ID);
    }

    public function testNewWithRedirectToContactActionIfForbidden(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->will($this->throwException(new ForbiddenException('create')));

        $this->customObjectModel->expects($this->never())
            ->method('fetchEntity');

        $this->customItemModel->expects($this->never())
            ->method('fetchEntity');

        $this->routeProvider->expects($this->never())
            ->method('buildNewRouteWithRedirectToContact');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->newWithRedirectToContactAction(static::OBJECT_ID, static::CONTACT_ID);
    }

    public function testNewAction(): void
    {
        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

        $this->customItemModel->expects($this->once())
            ->method('populateCustomFields')
            ->willReturn($this->customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildNewRoute')
            ->with(self::OBJECT_ID);

        $this->assertRenderFormForItem($this->customItem);

        $this->formController->newAction(self::OBJECT_ID);
    }

    public function testNewWithRedirectToContactAction(): void
    {
        $customObject = new class() extends CustomObject {
            public function getId()
            {
                return FormControllerTest::OBJECT_ID;
            }
        };

        $customItem = new class($customObject) extends CustomItem {
            public function getId()
            {
                return FormControllerTest::ITEM_ID;
            }
        };

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($customObject);

        $this->customItemModel->expects($this->once())
            ->method('populateCustomFields')
            ->willReturn($customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildNewRouteWithRedirectToContact')
            ->with(self::OBJECT_ID);

        $this->assertRenderFormForItem($customItem, static::CONTACT_ID);

        $this->formController->newWithRedirectToContactAction(static::OBJECT_ID, static::CONTACT_ID);
    }

    public function testNewWithRedirectToContactActionWithChildObject(): void
    {
        $customObject = new class() extends CustomObject {
            public function getId()
            {
                return FormControllerTest::OBJECT_ID;
            }

            public function getRelationshipObject(): CustomObject
            {
                return new class() extends CustomObject {
                    public function getId()
                    {
                        return 555;
                    }
                };
            }
        };

        $this->permissionProvider->expects($this->once())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($customObject);

        $this->customItemModel->expects($this->exactly(2))
            ->method('populateCustomFields')
            ->will(
                $this->returnCallback(
                    function ($newCustomItem) {
                        return $newCustomItem;
                    }
                )
            );

        $this->routeProvider->expects($this->once())
            ->method('buildNewRouteWithRedirectToContact')
            ->with(self::OBJECT_ID);

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with(self::OBJECT_ID, 0)
            ->willReturn('https://list.items');

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomItemType::class,
                $this->isInstanceOf(CustomItem::class),
                [
                    'action'    => 'https://list.items',
                    'objectId'  => self::OBJECT_ID,
                    'contactId' => static::CONTACT_ID,
                    'cancelUrl' => null,
                ]
            )
            ->willReturn($this->form);

        $this->formController->newWithRedirectToContactAction(static::OBJECT_ID, static::CONTACT_ID);
    }

    public function testEditActionIfCustomItemNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willThrowException(new NotFoundException());

        $this->routeProvider->expects($this->never())
            ->method('buildEditRoute');

        $this->formController->editAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testEditActionIfCustomItemForbidden(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRoute');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->editAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testEditAction(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customItem);

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->willReturn(false);

        $this->customItemModel->expects($this->once())
            ->method('lockEntity');

        $this->routeProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID);

        $this->assertRenderFormForItem($this->customItem);

        $this->formController->editAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testEditWithRedirectToContactAction(): void
    {
        $customObject = new class() extends CustomObject {
            public function getId()
            {
                return FormControllerTest::OBJECT_ID;
            }
        };

        $customItem = new class($customObject) extends CustomItem {
            public function getId()
            {
                return FormControllerTest::ITEM_ID;
            }
        };

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($customItem);

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->willReturn(false);

        $this->customItemModel->expects($this->once())
            ->method('lockEntity');

        $this->routeProvider->expects($this->once())
            ->method('buildEditRouteWithRedirectToContact')
            ->with(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);

        $this->assertRenderFormForItem($customItem, static::CONTACT_ID);

        $this->formController->editWithRedirectToContactAction(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);
    }

    public function testEditWithRedirectToContactActionWithChildObject(): void
    {
        $customObject = new class() extends CustomObject {
            public function getId()
            {
                return FormControllerTest::OBJECT_ID;
            }

            public function getRelationshipObject(): CustomObject
            {
                return new class() extends CustomObject {
                    public function getId()
                    {
                        return 555;
                    }
                };
            }
        };

        $customItem = new class($customObject) extends CustomItem {
            public function getId()
            {
                return FormControllerTest::ITEM_ID;
            }

            public function findChildCustomItem(): CustomItem
            {
                return new class($this->getCustomObject()->getRelationshipObject()) extends CustomItem {
                    public function getId()
                    {
                        return 777;
                    }
                };
            }
        };

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($customItem);

        $this->customItemModel->expects($this->once())
            ->method('populateCustomFields')
            ->with(
                $this->callback(
                    function (CustomItem $childCustomItem) {
                        Assert::assertSame(555, $childCustomItem->getCustomObject()->getId());
                        Assert::assertSame(777, $childCustomItem->getId());

                        return true;
                    }
                )
            )
            ->willReturnArgument(0);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($customItem);

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->willReturn(false);

        $this->customItemModel->expects($this->once())
            ->method('lockEntity');

        $this->routeProvider->expects($this->once())
            ->method('buildEditRouteWithRedirectToContact')
            ->with(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);

        $this->assertRenderFormForItem($customItem, static::CONTACT_ID);

        $this->formController->editWithRedirectToContactAction(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);
    }

    public function testEditWithRedirectToContactActionIfCustomItemNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRouteWithRedirectToContact');

        $this->formController->editWithRedirectToContactAction(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);
    }

    public function testEditWithRedirectToContactActionIfCustomItemForbidden(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRouteWithRedirectToContact');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->editWithRedirectToContactAction(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);
    }

    public function testEditWithRedirectToContactActionWhenTheItemIsLocked()
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(true);

        $this->routeProvider->expects($this->once())
            ->method('buildEditRouteWithRedirectToContact')
            ->with(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);

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

        $this->formController->editWithRedirectToContactAction(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);
    }

    public function testEditActionWhenTheItemIsLocked()
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->customItemModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(true);

        $this->routeProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID);

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

        $this->formController->editAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testCloneAction(): void
    {
        $this->customItem->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canClone');

        $this->routeProvider->expects($this->once())
            ->method('buildCloneRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID);

        $this->assertRenderFormForItem($this->customItem);

        $this->formController->cloneAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testCloneActionIfCustomItemNotFound(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->routeProvider->expects($this->never())
            ->method('buildCloneRoute');

        $this->formController->cloneAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testCloneActionIfCustomItemForbidden(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canClone')
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->routeProvider->expects($this->never())
            ->method('buildCloneRoute');

        $this->expectException(AccessDeniedHttpException::class);

        $this->formController->cloneAction(self::OBJECT_ID, self::ITEM_ID);
    }

    private function assertRenderFormForItem(CustomItem $customItem, ?int $contactId = null): void
    {
        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://list.items');

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomItemType::class,
                $customItem,
                [
                    'action'    => 'https://list.items',
                    'objectId'  => self::OBJECT_ID,
                    'contactId' => $contactId,
                    'cancelUrl' => null,
                ]
            )
            ->willReturn($this->form);
    }
}
