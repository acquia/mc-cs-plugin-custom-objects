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

use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use Mautic\CoreBundle\Event\MenuEvent;
use MauticPlugin\CustomObjectsBundle\EventListener\MenuSubscriber;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

class MenuSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $customObjectModel;

    private $configProvider;

    private $menuEvent;

    /**
     * @var MenuSubscriber
     */
    private $menuSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customObjectModel = $this->createMock(CustomObjectModel::class);
        $this->configProvider    = $this->createMock(ConfigProvider::class);
        $this->menuEvent         = $this->createMock(MenuEvent::class);
        $this->menuSubscriber    = new MenuSubscriber($this->customObjectModel, $this->configProvider);
    }

    public function testPluginDisabled(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(false);

        $this->menuEvent->expects($this->never())
            ->method('getType');

        $this->menuSubscriber->onBuildMenu($this->menuEvent);
    }

    public function testTypeNotMain(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->menuEvent->expects($this->exactly(2))
            ->method('getType')
            ->willReturn('not-main-or-admin');

        $this->customObjectModel->expects($this->never())
            ->method('fetchAllPublishedEntities');

        $this->menuEvent->expects($this->never())
            ->method('addMenuItems');

        $this->menuSubscriber->onBuildMenu($this->menuEvent);
    }

    public function testNoCustomObjects(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->menuEvent->expects($this->exactly(2))
            ->method('getType')
            ->willReturn('main');

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([]);

        $this->menuEvent->expects($this->never())
            ->method('addMenuItems');

        $this->menuSubscriber->onBuildMenu($this->menuEvent);
    }

    public function testSomeCustomObjects(): void
    {
        $customObject = $this->createMock(CustomObject::class);
        $customObject->method('getName')->willReturn('Test Object');
        $customObject->method('getId')->willReturn(333);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->menuEvent->expects($this->exactly(2))
            ->method('getType')
            ->willReturn('main');

        $this->customObjectModel->expects($this->once())
            ->method('fetchAllPublishedEntities')
            ->willReturn([$customObject]);

        $this->menuEvent->expects($this->at(0))
            ->method('addMenuItems')
            ->willReturn([
                'priority' => 61,
                'items'    => [
                    'custom.object.title' => [
                        'access'    => 'custom_objects:custom_objects:view',
                        'iconClass' => 'fa-list-alt',
                        'id'        => 'mautic_custom_object_list',
                    ],
                ],
            ]);

        $this->menuEvent->expects($this->at(2))
            ->method('addMenuItems')
            ->willReturn([
                'items' => [
                    'Test Object' => [
                        'route'           => CustomItemRouteProvider::ROUTE_LIST,
                        'routeParameters' => ['objectId' => 333, 'page' => 1],
                        'access'          => 'custom_fields:custom_fields:view',
                        'id'              => 'mautic_custom_object_333',
                        'parent'          => 'custom.object.title',
                    ],
                ],
            ]);

        $this->menuSubscriber->onBuildMenu($this->menuEvent);
    }

    public function testAdminMenu(): void
    {
        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->menuEvent->expects($this->exactly(2))
            ->method('getType')
            ->willReturn('admin');

        $this->customObjectModel->expects($this->never())
            ->method('fetchAllPublishedEntities');

        $this->menuEvent->expects($this->once())
            ->method('addMenuItems')
            ->willReturn([
                'priority' => 61,
                'items'    => [
                    'custom.object.config.menu.title' => [
                        'id'        => CustomObjectRouteProvider::ROUTE_LIST,
                        'route'     => CustomObjectRouteProvider::ROUTE_LIST,
                        'access'    => 'custom_objects:custom_objects:view',
                        'iconClass' => 'fa-list-alt',
                    ],
                ],
            ]);

        $this->menuSubscriber->onBuildMenu($this->menuEvent);
    }
}
