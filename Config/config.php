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
                'controller' => 'CustomObjectsBundle:CustomField\List:list',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            'mautic_custom_field_view' => [
                'path'       => '/custom/field/view/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomField\View:view',
                'method'     => 'GET',
            ],
            'mautic_custom_field_new' => [
                'path'       => '/custom/field/new',
                'controller' => 'CustomObjectsBundle:CustomField\New:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_field_edit' => [
                'path'       => '/custom/field/edit/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomField\Edit:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_field_clone' => [
                'path'       => '/custom/field/clone/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomField\Clone:clone',
                'method'     => 'GET',
            ],
            'mautic_custom_field_cancel' => [
                'path'       => '/custom/field/cancel/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomField\Cancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_field_save' => [
                'path'       => '/custom/field/save/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomField\Save:save',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_field_delete' => [
                'path'       => '/custom/field/delete/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomField\Delete:delete',
                'method'     => 'GET',
            ],

            // Custom Objects
            'mautic_custom_object_list' => [
                'path'       => '/custom/object/{page}',
                'controller' => 'CustomObjectsBundle:CustomObject\List:list',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            'mautic_custom_object_view' => [
                'path'       => '/custom/object/view/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\View:view',
                'method'     => 'GET',
            ],
            'mautic_custom_object_new' => [
                'path'       => '/custom/object/new',
                'controller' => 'CustomObjectsBundle:CustomObject\New:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_object_edit' => [
                'path'       => '/custom/object/edit/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Edit:renderForm',
                'method'     => 'GET',
            ],
            'mautic_custom_object_clone' => [
                'path'       => '/custom/object/clone/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Clone:clone',
                'method'     => 'GET',
            ],
            'mautic_custom_object_cancel' => [
                'path'       => '/custom/object/cancel/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Cancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_object_save' => [
                'path'       => '/custom/object/save/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Save:save',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            'mautic_custom_object_delete' => [
                'path'       => '/custom/object/delete/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Delete:delete',
                'method'     => 'GET',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'custom.object.config.menu.parent.title' => [
                'access'    => 'custom_objects:custom_objects:view',
                'iconClass' => 'fa-list-alt',
                'id'        => 'mautic_custom_config_parent_list',
            ],
            'custom.object.config.menu.title' => [
                'route'     => 'mautic_custom_object_list',
                'access'    => 'custom_objects:custom_objects:view',
                'id'        => 'mautic_custom_object_config_list',
                'parent'    => 'custom.object.config.menu.parent.title',
            ],
            'custom.field.config.menu.title' => [
                'route'     => 'mautic_custom_field_list',
                'access'    => 'custom_fields:custom_fields:view',
                'id'        => 'mautic_custom_field_list',
                'parent'    => 'custom.object.config.menu.parent.title',
            ],
        ],
    ],

    'services' => [
        'controllers' => [

            // Custom Fields
            'custom_field.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\ListController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\ViewController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\NewController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\EditController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\CloneController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\SaveController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\DeleteController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\CancelController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ViewController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\NewController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\EditController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\CloneController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\SaveController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\DeleteController::class,
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
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomObject\CancelController::class,
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
            'custom_object.menu.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\MenuSubscriber::class,
                'arguments' => [
                    'mautic.custom.model.object',
                ],
            ],
            'custom_field.type.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldTypeSubscriber::class,
                'arguments' => [
                    'translator',
                ],
            ],
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
                    'custom_field.type.provider',
                ],
            ],
        ],
        'other' => [
            'custom_field.type.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
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
