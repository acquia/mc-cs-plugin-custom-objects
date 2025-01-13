<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\MenuEvent;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\MenuSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use PHPUnit\Framework\TestCase;

class MenuSubscriberTest extends TestCase
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
            ->method('getMasterCustomObjects')
            ->willReturn([]);

        $this->menuEvent->expects($this->never())
            ->method('addMenuItems');

        $this->menuSubscriber->onBuildMenu($this->menuEvent);
    }

    public function testSomeCustomObjects(): void
    {
        $customObject1 = $this->createMock(CustomObject::class);
        $customObject1->method('getName')->willReturn('Test Object 1');
        $customObject1->method('getId')->willReturn(333);
        $customObject1->method('getType')->willReturn(CustomObject::TYPE_MASTER);

        // Custom Objects of type 'Relationship' should not appear in the menu
        $customObject2 = $this->createMock(CustomObject::class);
        $customObject2->method('getName')->willReturn('Test Object 2');
        $customObject2->method('getId')->willReturn(123);
        $customObject2->method('getType')->willReturn(CustomObject::TYPE_RELATIONSHIP);

        $this->configProvider->expects($this->once())
            ->method('pluginIsEnabled')
            ->willReturn(true);

        $this->menuEvent->expects($this->exactly(2))
            ->method('getType')
            ->willReturn('main');

        $this->customObjectModel->expects($this->once())
            ->method('getMasterCustomObjects')
            ->willReturn([$customObject1, $customObject2]);

        $this->menuEvent
            ->method('addMenuItems')
            ->willReturnOnConsecutiveCalls(
                [
                    'priority' => 61,
                    'items'    => [
                        'custom.object.title' => [
                            'access'    => 'custom_objects:custom_objects:view',
                            'iconClass' => 'fa-list-alt',
                            'id'        => 'mautic_custom_object_list',
                        ],
                    ],
                ],
                [
                    'items' => [
                        'Test Object' => [
                            'route'           => CustomItemRouteProvider::ROUTE_LIST,
                            'routeParameters' => ['objectId' => 333, 'page' => 1],
                            'access'          => 'custom_fields:custom_fields:view',
                            'id'              => 'mautic_custom_object_333',
                            'parent'          => 'custom.object.title',
                        ],
                    ],
                ]
            );

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
            ->method('addMenuItems');

        $this->menuSubscriber->onBuildMenu($this->menuEvent);
    }
}
