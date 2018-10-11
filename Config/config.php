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
            'mautic_custom_object_structures_list' => [
                'path'       => '/custom/object/structures/{page}',
                'controller' => 'custom_object_structures.list_controller:listAction',
            ],
            'mautic_custom_object_structures_new' => [
                'path'       => '/custom/object/structures/new',
                'controller' => 'custom_object_structures.new_controller:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_object_structures_cancel' => [
                'path'       => '/custom/object/structures/cancel',
                'controller' => 'custom_object_structures.cancel_controller:redirectToList',
                'method'     => 'POST',
            ],
            'mautic_custom_object_structures_save' => [
                'path'       => '/custom/object/structures/save',
                'controller' => 'custom_object_structures.save_controller:save',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'custom.object.structure.title' => [
                'route'     => 'mautic_custom_object_structures_list',
                'access'    => 'custom_object_structures:objects:view',
                'iconClass' => 'fa-list-alt',
                'id'        => 'mautic_custom_object_structures_list',
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
            'custom_object_structures.new_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureNewController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ]
                ]
            ],
            'custom_object_structures.save_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureSaveController::class,
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
            'custom_object_structures.cancel_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureCancelController::class,
                'arguments' => [
                    'session',
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
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
    ],
];
