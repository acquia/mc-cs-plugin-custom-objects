<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Custom Objects',
    'description' => 'Adds custom objects and fields features to Mautic',
    'version'     => '0.0',
    'author'      => 'Mautic, Inc.',

    'routes' => [
        'main' => [
            'custom_objects_list' => [
                'path'       => '/custom/objects/{page}',
                'controller' => 'custom_objects.list_controller:listAction',
            ],
            'mautic_custom_objects_action' => [
                'path'       => '/custom/objects/{objectAction}/{objectId}',
                'controller' => 'custom_objects.action_controller:executeAction',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'custom.objects.title' => [
                'route'     => 'custom_objects_index',
                'access'    => 'custom_objects:objects:view',
                'iconClass' => 'fa-list-alt',
                'id'        => 'custom_objects_index',
            ],
        ],
    ],

    'services' => [
        'controllers' => [
            'custom_objects.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectListController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'custom_objects.model.list',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ]
                ]
            ],
            'custom_objects.action_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectActionController::class,
                'arguments' => [
                    'request_stack',
                    'router',
                    'session',
                    'form.factory',
                    'translator',
                    'custom_objects.model.action',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ]
                ]
            ],
        ],
        'models' => [
            'custom_objects.model.list' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectListModel::class,
                'arguments' => [
                ],
            ],
            'custom_objects.model.action' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectActionModel::class,
                'arguments' => [
                ],
            ],
        ],
    ],
];
