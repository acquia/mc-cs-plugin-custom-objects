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

class MenuSubscriber extends CommonSubscriber
{
    /**
     * @var CustomObjectModel
     */
    private $customObjectModel;

    /**
     * @param CustomObjectModel $customObjectModel
     */
    public function __construct(CustomObjectModel $customObjectModel)
    {
        $this->customObjectModel = $customObjectModel;
    }

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
        if ('main' !== $event->getType()) {
            return;
        }

        $customObjects = $this->getPublicCustomObjects();

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
                            'route'     => 'mautic_custom_field_list', // @todo change this when the right route will be available
                            'routeParameters' => ['page' => 2],
                            'access'    => 'custom_fields:custom_fields:view',
                            'id'        => 'mautic_custom_object_'.$customObject->getId(),
                            'parent'    => 'custom.object.title',
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * @return array
     */
    private function getPublicCustomObjects(): array
    {
        return $this->customObjectModel->getEntities([
            'ignore_paginator' => true,
            'filter'           => [
                'force' => [
                    [
                        'column' => 'e.isPublished',
                        'value'  => true,
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ]);
    }
}
