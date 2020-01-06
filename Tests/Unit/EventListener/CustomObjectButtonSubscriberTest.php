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
use Mautic\CoreBundle\Event\CustomButtonEvent;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\CustomObjectButtonSubscriber;

class CustomObjectButtonSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $itemPermissionProvider;
    private $objectPermissionProvider;
    private $objectRouteProvider;
    private $itemRouteProvider;
    private $customObject;
    private $event;

    /**
     * @var CustomObjectButtonSubscriber
     */
    private $subscrber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemPermissionProvider   = $this->createMock(CustomItemPermissionProvider::class);
        $this->objectPermissionProvider = $this->createMock(CustomObjectPermissionProvider::class);
        $this->objectRouteProvider      = $this->createMock(CustomObjectRouteProvider::class);
        $this->itemRouteProvider        = $this->createMock(CustomItemRouteProvider::class);
        $this->customObject             = $this->createMock(CustomObject::class);
        $this->event                    = $this->createMock(CustomButtonEvent::class);
        $this->subscrber                = new CustomObjectButtonSubscriber(
            $this->objectPermissionProvider,
            $this->objectRouteProvider,
            $this->itemPermissionProvider,
            $this->itemRouteProvider
        );
    }

    public function testInjectViewButtonsForListRoute(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomObjectRouteProvider::ROUTE_LIST);

        $this->customObject->expects($this->any())
            ->method('getId')
            ->willReturn(555);

        $this->event->expects($this->once())
            ->method('getItem')
            ->willReturn($this->customObject);

        $this->objectPermissionProvider->expects($this->once())
            ->method('canDelete')
            ->with($this->customObject);

        $this->objectRouteProvider->expects($this->once())
            ->method('buildDeleteRoute')
            ->with(555)
            ->willReturn('generated/delete/route');

        $this->objectPermissionProvider->expects($this->once())
            ->method('canClone')
            ->with($this->customObject);

        $this->objectRouteProvider->expects($this->once())
            ->method('buildCloneRoute')
            ->with(555)
            ->willReturn('generated/clone/route');

        $this->objectPermissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customObject);

        $this->objectRouteProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(555)
            ->willReturn('generated/edit/route');

        $this->objectPermissionProvider->expects($this->once())
            ->method('canCreate');

        $this->objectRouteProvider->expects($this->once())
            ->method('buildNewRoute')
            ->willReturn('generated/new/route');

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
                    'href' => 'generated/new/route',
                ],
                'btnText'   => 'mautic.core.form.new',
                'iconClass' => 'fa fa-plus',
                'priority'  => 500,
            ]]);

        $this->subscrber->injectViewButtons($this->event);
    }

    public function testInjectViewButtonsForListRouteWithoutPermissions(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomObjectRouteProvider::ROUTE_LIST);

        $this->customObject->expects($this->any())
            ->method('getId')
            ->willReturn(555);

        $this->event->expects($this->once())
            ->method('getItem')
            ->willReturn($this->customObject);

        $this->objectPermissionProvider->expects($this->once())
            ->method('canDelete')
            ->with($this->customObject)
            ->will($this->throwException(new ForbiddenException('delete')));

        $this->objectPermissionProvider->expects($this->once())
            ->method('canClone')
            ->with($this->customObject)
            ->will($this->throwException(new ForbiddenException('clone')));

        $this->objectPermissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customObject)
            ->will($this->throwException(new ForbiddenException('edit')));

        $this->objectPermissionProvider->expects($this->once())
            ->method('canCreate')
            ->will($this->throwException(new ForbiddenException('create')));

        $this->event->expects($this->never())
            ->method('addButton');

        $this->subscrber->injectViewButtons($this->event);
    }

    public function testInjectViewButtonsForViewRoute(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomObjectRouteProvider::ROUTE_VIEW);

        $this->customObject->expects($this->any())
            ->method('getId')
            ->willReturn(555);

        $this->event->expects($this->any())
            ->method('getItem')
            ->willReturn($this->customObject);

        $this->objectPermissionProvider->expects($this->once())
            ->method('canDelete')
            ->with($this->customObject);

        $this->objectRouteProvider->expects($this->once())
            ->method('buildDeleteRoute')
            ->with(555)
            ->willReturn('generated/delete/route');

        $this->objectPermissionProvider->expects($this->once())
            ->method('canClone')
            ->with($this->customObject);

        $this->objectRouteProvider->expects($this->once())
            ->method('buildCloneRoute')
            ->with(555)
            ->willReturn('generated/clone/route');

        $this->objectPermissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customObject);

        $this->objectRouteProvider->expects($this->once())
            ->method('buildEditRoute')
            ->with(555)
            ->willReturn('generated/edit/route');

        $this->objectRouteProvider->expects($this->once())
            ->method('buildListRoute')
            ->willReturn('generated/list/route');

        $this->itemPermissionProvider->expects($this->once())
            ->method('canViewAtAll')
            ->with(555);

        $this->itemRouteProvider->expects($this->once())
            ->method('buildListRoute')
            ->willReturn('generated/item/list/route');

        $this->itemPermissionProvider->expects($this->once())
            ->method('canCreate')
            ->with(555);

        $this->itemRouteProvider->expects($this->once())
            ->method('buildNewRoute')
            ->willReturn('generated/item/new/route');

        $this->event->expects($this->exactly(6))
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
            ]], [[
                'attr' => [
                    'href' => 'generated/item/list/route',
                ],
                'btnText'   => 'custom.items.view.link',
                'iconClass' => 'fa fa-fw fa-list-alt',
                'priority'  => 0,
            ]], [[
                'attr' => [
                    'href' => 'generated/item/new/route',
                ],
                'btnText'   => 'custom.item.create.link',
                'iconClass' => 'fa fa-fw fa-plus',
                'priority'  => 0,
            ]]);

        $this->subscrber->injectViewButtons($this->event);
    }

    public function testInjectViewButtonsForViewRouteWithoutPermissions(): void
    {
        $this->event->expects($this->any())
            ->method('getRoute')
            ->willReturn(CustomObjectRouteProvider::ROUTE_VIEW);

        $this->customObject->expects($this->any())
            ->method('getId')
            ->willReturn(555);

        $this->event->expects($this->any())
            ->method('getItem')
            ->willReturn($this->customObject);

        $this->objectPermissionProvider->expects($this->once())
            ->method('canDelete')
            ->with($this->customObject)
            ->will($this->throwException(new ForbiddenException('create')));

        $this->objectPermissionProvider->expects($this->once())
            ->method('canClone')
            ->with($this->customObject)
            ->will($this->throwException(new ForbiddenException('create')));

        $this->objectPermissionProvider->expects($this->once())
            ->method('canEdit')
            ->with($this->customObject)
            ->will($this->throwException(new ForbiddenException('create')));

        $this->itemPermissionProvider->expects($this->once())
            ->method('canViewAtAll')
            ->with(555)
            ->will($this->throwException(new ForbiddenException('create')));

        $this->itemPermissionProvider->expects($this->once())
            ->method('canCreate')
            ->with(555)
            ->will($this->throwException(new ForbiddenException('create')));

        $this->objectRouteProvider->expects($this->once())
            ->method('buildListRoute')
            ->willReturn('generated/list/route');

        $this->event->expects($this->once())
            ->method('addButton')
            ->with([
                'attr' => [
                    'href' => 'generated/list/route',
                ],
                'btnText'   => 'mautic.core.form.close',
                'iconClass' => 'fa fa-fw fa-remove',
                'priority'  => 400,
            ]);

        $this->subscrber->injectViewButtons($this->event);
    }
}
