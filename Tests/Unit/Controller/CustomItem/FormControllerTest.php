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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\CustomItem;

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
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FormControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private const ITEM_ID = 22;

    private const CONTACT_ID = 11;

    private const CANCEL_URL = 'https://cancel.url';

    private $customItemModel;
    private $customObjectModel;
    private $formFactory;
    private $permissionProvider;
    private $routeProvider;
    private $lockFlashMessageHelper;
    private $customObject;
    private $customItem;
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
        $this->request->method('isXmlHttpRequest')->willReturn(true);
        $this->request->method('getRequestUri')->willReturn('https://a.b');
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

        $this->assertRenderFormForItem();

        $this->formController->newAction(self::OBJECT_ID);
    }

    public function testNewWithRedirectToContactAction(): void
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
            ->method('buildNewRouteWithRedirectToContact')
            ->with(self::OBJECT_ID);

        $this->assertRenderFormForItem(static::CONTACT_ID);

        $this->formController->newWithRedirectToContactAction(static::OBJECT_ID, static::CONTACT_ID);
    }

    public function testEditActionIfCustomObjectNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->will($this->throwException(new NotFoundException('Item not found message')));

        $this->customItemModel->expects($this->never())
            ->method('fetchEntity');

        $this->routeProvider->expects($this->never())
            ->method('buildEditRoute');

        $this->formController->editAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testEditActionIfCustomItemNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRoute');

        $this->formController->editAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testEditActionIfCustomItemForbidden(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

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
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

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

        $this->assertRenderFormForItem();

        $this->formController->editAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testEditWithRedirectToContactAction(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

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
            ->method('buildEditRouteWithRedirectToContact')
            ->with(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);

        $this->assertRenderFormForItem(static::CONTACT_ID);

        $this->formController->editWithRedirectToContactAction(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);
    }

    public function testEditWithRedirectToContactActionIfCustomItemNotFound(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->will($this->throwException(new NotFoundException()));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRouteWithRedirectToContact');

        $this->formController->editWithRedirectToContactAction(self::OBJECT_ID, self::ITEM_ID, static::CONTACT_ID);
    }

    public function testEditWithRedirectToContactActionIfCustomItemForbidden(): void
    {
        $this->customObjectModel->expects($this->once())
            ->method('fetchEntity')
            ->with(self::OBJECT_ID)
            ->willReturn($this->customObject);

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

    public function testCloneAction(): void
    {
        $this->customItem->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($this->customObject);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canClone');

        $this->routeProvider->expects($this->once())
            ->method('buildCloneRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID);

        $this->assertRenderFormForItem();

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

    private function assertRenderFormForItem(?int $contactId = null): void
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
                $this->customItem,
                [
                    'action' => 'https://list.items',
                    'objectId' => self::OBJECT_ID,
                    'contactId' => $contactId,
                    'cancelUrl' => null,
                ]
            )
            ->willReturn($this->form);
    }
}
