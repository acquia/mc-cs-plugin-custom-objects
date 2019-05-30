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

use MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper;
use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Controller\CustomItem\SaveController;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Tests\Unit\Controller\ControllerTestCase;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormFactoryInterface;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ClickableInterface;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\Form\FormError;

class SaveControllerTest extends ControllerTestCase
{
    private const OBJECT_ID = 33;

    private const ITEM_ID = 22;

    private $formFactory;
    private $customItemModel;
    private $customObjectModel;
    private $flashBag;
    private $permissionProvider;
    private $routeProvider;
    private $lockFlashMessageHelper;
    private $customItem;
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
        $this->request                 = $this->createMock(Request::class);
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
        $this->customItem->expects($this->once())
            ->method('getName')
            ->willReturn('Umpalumpa');

        $this->customItem->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(false);

        $this->routeProvider->expects($this->exactly(2))
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://edit.item');

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
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

        $this->request->expects($this->once())
            ->method('setMethod')
            ->with(Request::METHOD_GET);

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }

    public function testSaveActionIfNewCustomItemIsForbidden(): void
    {
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

        $this->form->expects($this->at(0))
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->at(1))
            ->method('isValid')
            ->willReturn(false);

        $this->customItemModel->expects($this->never())
            ->method('save');

        $this->saveController->saveAction(self::OBJECT_ID);
    }

    public function testSaveActionForNewCustomItemWithInvalidCustomFieldValue(): void
    {
        $this->customItemModel->expects($this->once())
            ->method('fetchEntity')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canEdit');

        $this->permissionProvider->expects($this->never())
            ->method('canCreate');

        $this->customObjectModel->expects($this->once())
            ->method('isLocked')
            ->with($this->customItem)
            ->willReturn(false);

        $this->routeProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('https://edit.item');

        $this->routeProvider->expects($this->once())
            ->method('buildSaveRoute')
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

        $this->form->expects($this->at(0))
            ->method('handleRequest')
            ->with($this->request);

        $this->form->expects($this->at(1))
            ->method('isValid')
            ->willReturn(true);

        $customField      = $this->createMock(CustomField::class);
        $CFValueException = new InvalidValueException('This field is invalid');
        $CFValueException->setCustomField($customField);

        $customField->expects($this->once())
            ->method('getId')
            ->willReturn(567);

        $this->customItemModel->expects($this->once())
            ->method('save')
            ->with($this->customItem)
            ->will($this->throwException($CFValueException));

        $this->form->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['custom_field_values'],
                [567],
                ['value']
            )->willReturnSelf();

        $this->form->expects($this->once())
            ->method('addError')
            ->with($this->callback(function (FormError $formError) {
                $this->assertSame('This field is invalid', $formError->getMessage());

                return true;
            }));

        $this->saveController->saveAction(self::OBJECT_ID, self::ITEM_ID);
    }
}
