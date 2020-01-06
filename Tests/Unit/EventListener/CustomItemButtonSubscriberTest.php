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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use Symfony\Component\Translation\TranslatorInterface;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomItemButtonSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomItemButtonSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private const OBJECT_ID  = 555;
    private const ITEM_ID    = 222;
    private const CONTACT_ID = 84;

    private $permissionProvider;
    private $routeProvider;
    private $translator;
    private $customItem;
    private $request;
    private $event;

    /**
     * @var CustomItemButtonSubscriber
     */
    private $subscrber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionProvider = $this->createMock(CustomItemPermissionProvider::class);
        $this->routeProvider      = $this->createMock(CustomItemRouteProvider::class);
        $this->translator         = $this->createMock(TranslatorInterface::class);
        $this->request            = $this->createMock(Request::class);
        $this->customItem         = $this->createMock(CustomItem::class);
        $this->event              = $this->createMock(CustomButtonEvent::class);
        $this->subscrber          = new CustomItemButtonSubscriber(
            $this->permissionProvider,
            $this->routeProvider,
            $this->translator
        );
    }

    public function testInjectViewButtonsForListRouteOnContactDetailPage(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomItemRouteProvider::ROUTE_LIST, ['route', ['objectId' => self::OBJECT_ID]]);

        $this->event->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->query = new ParameterBag(['filterEntityId' => self::CONTACT_ID, 'filterEntityType' => 'contact']);

        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->with(self::OBJECT_ID);

        $this->routeProvider->expects($this->once())
            ->method('buildUnlinkRoute')
            ->with(self::ITEM_ID, 'contact', self::CONTACT_ID)
            ->willReturn('generated/route');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.item.unlink')
            ->willReturn('translated string');

        $this->customItem->expects($this->once())
            ->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->event->expects($this->once())
            ->method('getItem')
            ->willReturn($this->customItem);

        $this->event->expects($this->once())
            ->method('addButton')
            ->with([
                'attr' => [
                    'href'        => '#',
                    'onclick'     => "CustomObjects.unlinkCustomItemFromEntity(this, event, 555, 'contact', 84, 'custom-object-555');",
                    'data-action' => 'generated/route',
                    'data-toggle' => '',
                ],
                'btnText'   => 'translated string',
                'iconClass' => 'fa fa-unlink',
                'priority'  => 500,
            ]);

        $this->subscrber->injectViewButtons($this->event);
    }

    public function testInjectViewButtonsForListRoute(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomItemRouteProvider::ROUTE_LIST, ['route', ['objectId' => self::OBJECT_ID]]);

        $this->event->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->query = new ParameterBag([]);

        $this->customItem->expects($this->any())
            ->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->event->expects($this->once())
            ->method('getItem')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canDelete')
            ->with($this->customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildDeleteRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('generated/delete/route');

        $this->permissionProvider->expects($this->once())
            ->method('canClone')
            ->with($this->customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildCloneRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('generated/clone/route');

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('generated/edit/route');

        $this->permissionProvider->expects($this->exactly(2))
            ->method('canCreate')
            ->with(self::OBJECT_ID);

        $this->routeProvider->expects($this->once())
            ->method('buildNewRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('generated/new/route');

        $this->routeProvider->expects($this->once())
            ->method('buildNewImportRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('generated/new/import/route');

        $this->permissionProvider->expects($this->once())
            ->method('canViewAtAll')
            ->with(self::OBJECT_ID);

        $this->routeProvider->expects($this->once())
            ->method('buildListImportRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('generated/list/import/route');

        $this->routeProvider->expects($this->once())
            ->method('buildBatchDeleteRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('generated/batch/delete/route');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.core.form.confirmbatchdelete')
            ->willReturn('translated string');

        $this->event->expects($this->exactly(7))
            ->method('addButton')
            ->withConsecutive([[
                'attr' => [
                    'href' => 'generated/delete/route',
                ],
                'btnText'   => 'mautic.core.form.delete',
                'iconClass' => 'fa fa-fw fa-trash-o text-danger',
                'priority'  => 0,
            ]], [[
                'attr' => [
                    'href' => 'generated/clone/route',
                ],
                'btnText'   => 'mautic.core.form.clone',
                'iconClass' => 'fa fa-copy',
                'priority'  => 300,
            ]], [[
                'attr' => [
                    'href' => 'generated/edit/route',
                ],
                'btnText'   => 'mautic.core.form.edit',
                'iconClass' => 'fa fa-pencil-square-o',
                'priority'  => 500,
            ]], [[
                'attr' => [
                    'href' => 'generated/new/route',
                ],
                'btnText'   => 'mautic.core.form.new',
                'iconClass' => 'fa fa-plus',
                'priority'  => 500,
            ]], [[
                'attr' => [
                    'href' => 'generated/new/import/route',
                ],
                'btnText'   => 'mautic.lead.import',
                'iconClass' => 'fa fa-upload',
                'priority'  => 350,
            ]], [[
                'attr' => [
                    'href' => 'generated/list/import/route',
                ],
                'btnText'   => 'mautic.lead.lead.import.index',
                'iconClass' => 'fa fa-history',
                'priority'  => 300,
            ]], [[
                'confirm' => [
                    'message'       => 'translated string',
                    'confirmAction' => 'generated/batch/delete/route',
                    'template'      => 'batchdelete',
                ],
                'btnText'   => 'mautic.core.form.delete',
                'iconClass' => 'fa fa-fw fa-trash-o text-danger',
                'priority'  => 0,
            ]]);

        $this->subscrber->injectViewButtons($this->event);
    }

    public function testInjectViewButtonsForListRouteIfForbidden(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomItemRouteProvider::ROUTE_LIST, ['route', ['objectId' => self::OBJECT_ID]]);

        $this->event->expects($this->exactly(2))
            ->method('getRequest')
            ->willReturn($this->request);

        $this->request->query = new ParameterBag([]);

        $this->customItem->expects($this->any())
            ->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->event->expects($this->once())
            ->method('getItem')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canDelete')
            ->with($this->customItem)
            ->will($this->throwException(new ForbiddenException('delete')));

        $this->routeProvider->expects($this->never())
            ->method('buildDeleteRoute');

        $this->permissionProvider->expects($this->once())
            ->method('canClone')
            ->with($this->customItem)
            ->will($this->throwException(new ForbiddenException('clone')));

        $this->routeProvider->expects($this->never())
            ->method('buildCloneRoute');

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customItem)
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->routeProvider->expects($this->never())
            ->method('buildEditRoute');

        $this->permissionProvider->expects($this->once())
            ->method('canCreate')
            ->with(self::OBJECT_ID)
            ->will($this->throwException(new ForbiddenException('create')));

        $this->routeProvider->expects($this->never())
            ->method('buildNewRoute');

        $this->routeProvider->expects($this->never())
            ->method('buildNewImportRoute');

        $this->routeProvider->expects($this->never())
            ->method('buildListImportRoute');

        $this->routeProvider->expects($this->never())
            ->method('buildBatchDeleteRoute');

        $this->event->expects($this->never())
            ->method('addButton');

        $this->subscrber->injectViewButtons($this->event);
    }

    public function testInjectViewButtonsForViewRoute(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomItemRouteProvider::ROUTE_VIEW, ['route', ['objectId' => self::OBJECT_ID]]);

        $this->customItem->expects($this->any())
            ->method('getId')
            ->willReturn(self::ITEM_ID);

        $this->event->expects($this->once())
            ->method('getItem')
            ->willReturn($this->customItem);

        $this->permissionProvider->expects($this->once())
            ->method('canDelete')
            ->with($this->customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildDeleteRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('generated/delete/route');

        $this->permissionProvider->expects($this->once())
            ->method('canClone')
            ->with($this->customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildCloneRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('generated/clone/route');

        $this->permissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customItem);

        $this->routeProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(self::OBJECT_ID, self::ITEM_ID)
            ->willReturn('generated/edit/route');

        $this->routeProvider->expects($this->once())
            ->method('buildListRoute')
            ->with(self::OBJECT_ID)
            ->willReturn('generated/list/route');

        $this->event->expects($this->exactly(4))
            ->method('addButton')
            ->withConsecutive([[
                'attr' => [
                    'href' => 'generated/delete/route',
                ],
                'btnText'   => 'mautic.core.form.delete',
                'iconClass' => 'fa fa-fw fa-trash-o text-danger',
                'priority'  => 0,
            ]], [[
                'attr' => [
                    'href' => 'generated/clone/route',
                ],
                'btnText'   => 'mautic.core.form.clone',
                'iconClass' => 'fa fa-copy',
                'priority'  => 300,
            ]], [[
                'attr' => [
                    'href' => 'generated/edit/route',
                ],
                'btnText'   => 'mautic.core.form.edit',
                'iconClass' => 'fa fa-pencil-square-o',
                'priority'  => 500,
            ]], [[
                'attr' => [
                    'href' => 'generated/list/route',
                ],
                'btnText'   => 'mautic.core.form.close',
                'iconClass' => 'fa fa-fw fa-remove',
                'priority'  => 400,
            ]]);

        $this->subscrber->injectViewButtons($this->event);
    }
}
