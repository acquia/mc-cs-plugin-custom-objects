<?php

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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;

class MenuSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @param CustomObjectModel $customObjectModel
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(CustomObjectModel $customObjectModel, CoreParametersHelper $coreParametersHelper)
    {
        $this->customObjectModel    = $customObjectModel;
        $this->coreParametersHelper = $coreParametersHelper;
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
        $isEnabled = $this->coreParametersHelper->getParameter(CustomObjectsBundle::CONFIG_PARAM_ENABLED);

        if (!$isEnabled) {
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
                            'access'          => 'custom_fields:custom_fields:view',
                            'id'              => 'mautic_custom_object_'.$customObject->getId(),
                            'parent'          => 'custom.object.title',
                        ],
                    ],
                ]
            );
        }
    }
}
