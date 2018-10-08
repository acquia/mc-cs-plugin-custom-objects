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

return [
    'name'        => 'Custom Objects',
    'description' => 'Adds custom objects and fields features to Mautic',
    'version'     => '0.0',
    'author'      => 'Mautic, Inc.',

    'routes' => [
        'main' => [
            'custom_objects_list' => [
                'path'       => '/custom/object/structures/{page}',
                'controller' => 'custom_object_structures.list_controller:listAction',
            ],
            'mautic_custom_objects_action' => [
                'path'       => '/custom/object/structures/{objectAction}/{objectId}',
                'controller' => 'custom_object_structures.action_controller:executeAction',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'custom.object.structures.title' => [
                'route'     => 'custom_object_structures_index',
                'access'    => 'custom_object_structures:objects:view',
                'iconClass' => 'fa-list-alt',
                'id'        => 'custom_object_structures_index',
            ],
        ],
    ],

    'services' => [
        'controllers' => [
            'custom_object_structures.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureListController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'custom_object_structures.model.list',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ]
                ]
            ],
            'custom_object_structures.action_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureActionController::class,
                'arguments' => [
                    'request_stack',
                    'router',
                    'session',
                    'form.factory',
                    'translator',
                    'custom_object_structures.model.action',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ]
                ]
            ],
        ],
        'models' => [
            'custom_object_structures.model.list' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureListModel::class,
                'arguments' => [
                ],
            ],
            'custom_object_structures.model.action' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureActionModel::class,
                'arguments' => [
                ],
            ],
        ],
    ],
];
