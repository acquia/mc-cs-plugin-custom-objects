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
            'mautic_custom_object_list' => [
                'path'       => '/custom/object/{page}',
                'controller' => 'CustomObjectsBundle:CustomObjectList:list',
                'method'     => 'GET',
            ],
            'mautic_custom_object_view' => [
                'path'       => '/custom/object/view/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObjectView:view',
                'method'     => 'GET',
            ],
            'mautic_custom_object_new' => [
                'path'       => '/custom/object/new',
                'controller' => 'CustomObjectsBundle:CustomObjectNew:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_object_edit' => [
                'path'       => '/custom/object/edit/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObjectEdit:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_object_clone' => [
                'path'       => '/custom/object/clone/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObjectClone:clone',
                'method'     => 'GET',
            ],
            'mautic_custom_object_cancel' => [
                'path'       => '/custom/object/cancel/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObjectCancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_object_save' => [
                'path'       => '/custom/object/save/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObjectSave:save',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_object_delete' => [
                'path'       => '/custom/object/delete/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObjectDelete:delete',
                'method'     => 'GET',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'custom.object.title' => [
                'route'     => 'mautic_custom_object_list',
                // 'access'    => 'custom_object:objects:view',
                'iconClass' => 'fa-list-alt',
                'id'        => 'mautic_custom_object_list',
            ],
        ],
    ],

    'services' => [
        'controllers' => [
            'custom_object.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectListController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'custom_object.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.view_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectViewController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'custom_object.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.new_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectNewController::class,
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
            'custom_object.edit_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectEditController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                    'custom_object.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.clone_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectCloneController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                    'custom_object.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.save_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectSaveController::class,
                'arguments' => [
                    'request_stack',
                    'router',
                    'session',
                    'form.factory',
                    'translator',
                    'custom_object.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.delete_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectDeleteController::class,
                'arguments' => [
                    'custom_object.model',
                    'session',
                    'translator',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_object.cancel_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectCancelController::class,
                'arguments' => [
                    'session',
                    'custom_object.model',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
        ],
        'models' => [
            'custom_object.model' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_object.repository',
                ],
            ],
        ],
        'repositories' => [
            'custom_object.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomObject::class,
                ],
            ],
        ],
        'events' => [
            'custom_object.button.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\ButtonSubscriber::class,
            ],
        ],
    ],
];
