<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;

class MenuSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param CustomObjectModel $customObjectModel
     * @param ConfigProvider    $configProvider
     */
    public function __construct(CustomObjectModel $customObjectModel, ConfigProvider $configProvider)
    {
        $this->customObjectModel = $customObjectModel;
        $this->configProvider    = $configProvider;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MENU => ['onBuildMenu', 9999],
        ];
    }

    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu(MenuEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled()) {
            return;
        }

        if ('main' !== $event->getType()) {
            return;
        }

        $customObjects = $this->customObjectModel->fetchAllPublishedEntities();

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
}
