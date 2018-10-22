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
                'controller' => 'CustomObjectsBundle:CustomObjectStructureList:list',
                'method'     => 'GET',
            ],
            'mautic_custom_object_structures_new' => [
                'path'       => '/custom/object/structures/new',
                'controller' => 'CustomObjectsBundle:CustomObjectStructureNew:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_object_structures_edit' => [
                'path'       => '/custom/object/structures/edit/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObjectStructureEdit:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_object_structures_cancel' => [
                'path'       => '/custom/object/structures/cancel',
                'controller' => 'CustomObjectsBundle:CustomObjectStructureCancel:cancel',
                'method'     => 'GET',
            ],
            'mautic_custom_object_structures_save' => [
                'path'       => '/custom/object/structures/save',
                'controller' => 'CustomObjectsBundle:CustomObjectStructureSave:save',
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
            'custom_object.structures.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureListController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'custom_object.structures.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.structures.new_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureNewController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.structures.edit_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureEditController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                    'custom_object.structures.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.structures.save_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureSaveController::class,
                'arguments' => [
                    'request_stack',
                    'router',
                    'session',
                    'form.factory',
                    'translator',
                    'custom_object.structures.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.structures.cancel_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectStructureCancelController::class,
                'arguments' => [
                    'session',
                    'custom_object.structures.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
        ],
        'models' => [
            'custom_object.structures.model' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectStructureModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_object.structures.repository',
                ],
            ],
        ],
        'repositories' => [
            'custom_object.structures.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomObjectStructure::class,
                ],
            ],
        ],
        'events' => [
            'custom_object.structures.button.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\ButtonSubscriber::class,
            ],
        ],
    ],
];
