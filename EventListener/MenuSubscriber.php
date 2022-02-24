<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(CustomObjectModel $customObjectModel, ConfigProvider $configProvider)
    {
        $this->customObjectModel = $customObjectModel;
        $this->configProvider    = $configProvider;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MENU => ['onBuildMenu', 9999],
        ];
    }

    public function onBuildMenu(MenuEvent $event): void
    {
        if ($this->configProvider->pluginIsEnabled()) {
            if ('main' === $event->getType()) {
                $this->addMainMenuItems($event);
            }

            if ('admin' === $event->getType()) {
                $this->addAdminMenuItems($event);
            }
        }
    }

    private function addMainMenuItems(MenuEvent $event): void
    {
        $customObjects = $this->customObjectModel->getMasterCustomObjects();

        if (!$customObjects) {
            return;
        }

        $event->addMenuItems(
            [
                'priority' => 61,
                'items'    => [
                    'custom.object.title' => [
                        'access'    => 'custom_objects:custom_objects:view',
                        'iconClass' => 'fa-list-alt',
                        'id'        => 'mautic_custom_object_list',
                    ],
                ],
            ]
        );

        foreach ($customObjects as $customObject) {
            $event->addMenuItems(
                [
                    'items' => [
                        $customObject->getName() => [
                            'route'           => CustomItemRouteProvider::ROUTE_LIST,
                            'routeParameters' => ['objectId' => $customObject->getId(), 'page' => 1],
                            'access'          => "custom_objects:{$customObject->getId()}:view",
                            'id'              => 'mautic_custom_object_'.$customObject->getId(),
                            'parent'          => 'custom.object.title',
                        ],
                    ],
                ]
            );
        }
    }

    private function addAdminMenuItems(MenuEvent $event): void
    {
        $event->addMenuItems(
            [
                'priority' => 61,
                'items'    => [
                    'custom.object.config.menu.title' => [
                        'id'        => CustomObjectRouteProvider::ROUTE_LIST,
                        'route'     => CustomObjectRouteProvider::ROUTE_LIST,
                        'access'    => 'custom_objects:custom_objects:view',
                        'iconClass' => 'fa-list-alt',
                    ],
                ],
            ]
        );
    }
}
