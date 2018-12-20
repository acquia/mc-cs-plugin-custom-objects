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

            // Custom Fields
            'mautic_custom_field_list' => [
                'path'       => '/custom/field/{page}',
                'controller' => 'CustomObjectsBundle:CustomFieldList:list',
                'method'     => 'GET|POST',
            ],
            'mautic_custom_field_view' => [
                'path'       => '/custom/field/view/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomFieldView:view',
                'method'     => 'GET',
            ],
            'mautic_custom_field_new' => [
                'path'       => '/custom/field/new',
                'controller' => 'CustomObjectsBundle:CustomFieldNew:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_field_edit' => [
                'path'       => '/custom/field/edit/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomFieldEdit:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_field_clone' => [
                'path'       => '/custom/field/clone/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomFieldClone:clone',
                'method'     => 'GET',
            ],
            'mautic_custom_field_cancel' => [
                'path'       => '/custom/object/cancel/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomFieldCancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_field_save' => [
                'path'       => '/custom/field/save/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomFieldSave:save',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_field_delete' => [
                'path'       => '/custom/field/delete/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomFieldDelete:delete',
                'method'     => 'GET',
            ],

            // Custom Objects
            'mautic_custom_object_list' => [
                'path'       => '/custom/object/{page}',
                'controller' => 'CustomObjectsBundle:CustomObjectList:list',
                'method'     => 'GET|POST',
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
                'access'    => 'custom_objects:custom_objects:view',
                'iconClass' => 'fa-list-alt',
                'id'        => 'mautic_custom_object_list',
            ],
            'custom.field.title' => [
                'route'     => 'mautic_custom_field_list',
                'access'    => 'custom_fields:custom_fields:view',
                'iconClass' => 'fa-list',
                'id'        => 'mautic_custom_fieldt_list',
                'parent'    => 'custom.object.title',
            ],
        ],
    ],

    'services' => [
        'controllers' => [

            // Custom Fields
            'custom_field.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldListController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.view_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldViewController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.new_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldNewController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                    'custom_field.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.edit_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldEditController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.clone_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldCloneController::class,
                'arguments' => [
                    'router',
                    'form.factory',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.save_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldSaveController::class,
                'arguments' => [
                    'request_stack',
                    'router',
                    'session',
                    'form.factory',
                    'translator',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',

                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.delete_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldDeleteController::class,
                'arguments' => [
                    'mautic.custom.model.field',
                    'session',
                    'translator',
                    'custom_field.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.cancel_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomFieldCancelController::class,
                'arguments' => [
                    'session',
                    'mautic.custom.model.field',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],

            // Custom Objects
            'custom_object.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObjectListController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',
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
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',
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
                    'custom_object.permission.provider',
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
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',
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
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',
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
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',

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
                    'mautic.custom.model.object',
                    'session',
                    'translator',
                    'custom_object.permission.provider',
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
                    'mautic.custom.model.object',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
        ],
        'models' => [
            'mautic.custom.model.field' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_field.repository',
                    'custom_field.permission.provider',
                    'mautic.helper.user',
                ],
            ],
            'mautic.custom.model.object' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_object.repository',
                    'custom_object.permission.provider',
                    'mautic.helper.user',
                ],
            ],
        ],
        'repositories' => [
            'custom_field.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomField::class,
                ],
            ],
            'custom_object.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomObject::class,
                ],
            ],
        ],
        'events' => [
            'custom_field.button.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldButtonSubscriber::class,
                'arguments' => [
                    'custom_field.permission.provider',
                ],
            ],
            'custom_object.button.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomObjectButtonSubscriber::class,
                'arguments' => [
                    'custom_object.permission.provider',
                ],
            ],
        ],
        'forms' => [
            'custom_field.field.form' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType::class,
                'arguments' => [
                    'mautic.custom.model.object',
                ],
            ],
        ],
        'other' => [
            'custom_field.permission.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'custom_object.permission.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
        ],
    ],
];
