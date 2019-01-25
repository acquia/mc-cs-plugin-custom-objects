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

use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\CustomObjectsBundle;

return [
    'name'        => 'Custom Objects',
    'description' => 'Adds custom objects and fields features to Mautic',
    'version'     => '0.0',
    'author'      => 'Mautic, Inc.',

    'routes' => [
        'main' => [

            // Custom Fields
            CustomFieldRouteProvider::ROUTE_LIST => [
                'path'       => '/custom/field/{page}',
                'controller' => 'CustomObjectsBundle:CustomField\List:list',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            CustomFieldRouteProvider::ROUTE_VIEW => [
                'path'       => '/custom/field/view/{fieldId}',
                'controller' => 'CustomObjectsBundle:CustomField\View:view',
                'method'     => 'GET',
            ],
            CustomFieldRouteProvider::ROUTE_FORM => [
                'path'       => '/custom/field/edit',
                'controller' => 'CustomObjectsBundle:CustomField\Form:renderForm',
                'method'     => 'GET',
            ],
            CustomFieldRouteProvider::ROUTE_CLONE => [
                'path'       => '/custom/field/clone/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomField\Clone:clone',
                'method'     => 'GET',
            ],
            CustomFieldRouteProvider::ROUTE_CANCEL => [
                'path'       => '/custom/field/cancel/{fieldId}',
                'controller' => 'CustomObjectsBundle:CustomField\Cancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'fieldId' => null,
                ],
            ],
            CustomFieldRouteProvider::ROUTE_SAVE => [
                'path'       => '/custom/field/save',
                'controller' => 'CustomObjectsBundle:CustomField\Save:save',
                'method'     => 'POST',
                'defaults'   => [
                    'fieldId' => null,
                ],
            ],
            CustomFieldRouteProvider::ROUTE_DELETE => [
                'path'       => '/custom/field/delete/{fieldId}',
                'controller' => 'CustomObjectsBundle:CustomField\Delete:delete',
                'method'     => 'GET|POST',
            ],

            // Custom Items
            CustomItemRouteProvider::ROUTE_LIST => [
                'path'       => '/custom/object/{objectId}/item/{page}',
                'controller' => 'CustomObjectsBundle:CustomItem\List:list',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            CustomItemRouteProvider::ROUTE_VIEW => [
                'path'       => '/custom/object/{objectId}/item/view/{itemId}',
                'controller' => 'CustomObjectsBundle:CustomItem\View:view',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_NEW => [
                'path'       => '/custom/object/{objectId}/item/new',
                'controller' => 'CustomObjectsBundle:CustomItem\New:renderForm',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_EDIT => [
                'path'       => '/custom/object/{objectId}/item/edit/{itemId}',
                'controller' => 'CustomObjectsBundle:CustomItem\Edit:renderForm',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_CLONE => [
                'path'       => '/custom/object/{objectId}/item/clone/{itemId}',
                'controller' => 'CustomObjectsBundle:CustomItem\Clone:clone',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_CANCEL => [
                'path'       => '/custom/object/{objectId}/item/cancel/{itemId}',
                'controller' => 'CustomObjectsBundle:CustomItem\Cancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'itemId' => null,
                ],
            ],
            CustomItemRouteProvider::ROUTE_SAVE => [
                'path'       => '/custom/object/{objectId}/item/save/{itemId}',
                'controller' => 'CustomObjectsBundle:CustomItem\Save:save',
                'method'     => 'POST',
                'defaults'   => [
                    'itemId' => null,
                ],
            ],
            CustomItemRouteProvider::ROUTE_DELETE => [
                'path'       => '/custom/object/{objectId}/item/delete/{itemId}',
                'controller' => 'CustomObjectsBundle:CustomItem\Delete:delete',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_LOOKUP => [
                'path'       => '/custom/object/{objectId}/item/lookup.json',
                'controller' => 'CustomObjectsBundle:CustomItem\Lookup:list',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_LINK => [
                'path'       => '/custom/item/{itemId}/link/{entityType}/{entityId}.json',
                'controller' => 'CustomObjectsBundle:CustomItem\Link:save',
                'method'     => 'POST',
            ],

            // Custom Objects
            CustomObjectRouteProvider::ROUTE_LIST => [
                'path'       => '/custom/object/{page}',
                'controller' => 'CustomObjectsBundle:CustomObject\List:list',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_VIEW => [
                'path'       => '/custom/object/view/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\View:view',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_NEW => [
                'path'       => '/custom/object/new',
                'controller' => 'CustomObjectsBundle:CustomObject\New:renderForm',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_EDIT => [
                'path'       => '/custom/object/edit/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Edit:renderForm',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_CLONE => [
                'path'       => '/custom/object/clone/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Clone:clone',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_CANCEL => [
                'path'       => '/custom/object/cancel/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Cancel:cancel',
                'method'     => 'GET',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_SAVE => [
                'path'       => '/custom/object/save/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Save:save',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_DELETE => [
                'path'       => '/custom/object/delete/{objectId}',
                'controller' => 'CustomObjectsBundle:CustomObject\Delete:delete',
                'method'     => 'GET',
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'custom.object.config.menu.title' => [
                'id'        => CustomObjectRouteProvider::ROUTE_LIST,
                'route'     => CustomObjectRouteProvider::ROUTE_LIST,
                'access'    => 'custom_objects:custom_objects:view',
                'iconClass' => 'fa-list-alt',
                'checks'    => [
                    'parameters' => [
                        CustomObjectsBundle::CONFIG_PARAM_ENABLED => true,
                    ],
                ],
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
                    'custom_field.route.provider',
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
                    'mautic.helper.core_parameters',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',
                    'custom_field.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_field.form_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomField\FormController::class,
                'arguments' => [
                    'form.factory',
                    'mautic.custom.model.field',
                    'custom_object.custom_field_factory',
                    'custom_field.permission.provider',
                    'custom_field.route.provider',
                    'mautic.custom.model.object',
                    'custom_object.route.provider',
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
                    'form.factory',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',
                    'custom_field.route.provider',
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
                    'session',
                    'form.factory',
                    'translator',
                    'mautic.custom.model.field',
                    'custom_field.permission.provider',
                    'custom_field.route.provider',
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
                    'custom_field.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],

            // Custom Items
            'custom_item.list_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ListController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'mautic.custom.model.item',
                    'mautic.custom.model.object',
                    'custom_item.permission.provider',
                    'custom_item.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.view_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ViewController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'mautic.helper.core_parameters',
                    'mautic.custom.model.item',
                    'custom_item.permission.provider',
                    'custom_item.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.new_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\NewController::class,
                'arguments' => [
                    'form.factory',
                    'custom_item.permission.provider',
                    'custom_item.route.provider',
                    'mautic.custom.model.object',
                    'mautic.custom.model.item',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.edit_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\EditController::class,
                'arguments' => [
                    'form.factory',
                    'mautic.custom.model.object',
                    'mautic.custom.model.item',
                    'custom_item.permission.provider',
                    'custom_item.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.clone_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\CloneController::class,
                'arguments' => [
                    'form.factory',
                    'mautic.custom.model.item',
                    'custom_item.permission.provider',
                    'custom_item.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.save_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\SaveController::class,
                'arguments' => [
                    'request_stack',
                    'session',
                    'form.factory',
                    'translator',
                    'mautic.custom.model.item',
                    'mautic.custom.model.object',
                    'custom_item.permission.provider',
                    'custom_item.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.delete_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\DeleteController::class,
                'arguments' => [
                    'mautic.custom.model.item',
                    'session',
                    'translator',
                    'custom_item.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.cancel_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\CancelController::class,
                'arguments' => [
                    'session',
                    'mautic.custom.model.item',
                    'custom_item.route.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.lookup_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LookupController::class,
                'arguments' => [
                    'request_stack',
                    'mautic.custom.model.item',
                    'custom_item.permission.provider',
                ],
                'methodCalls' => [
                    'setContainer' => [
                        '@service_container'
                    ],
                ],
            ],
            'custom_item.link_controller' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LinkController::class,
                'arguments' => [
                    'mautic.custom.model.item',
                    'custom_item.permission.provider',
                    'translator',
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
                    'custom_object.route.provider',
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
                    'custom_object.route.provider',
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
                    'form.factory',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
                    'custom_field.type.provider',
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
                    'form.factory',
                    'mautic.custom.model.object',
                    'mautic.custom.model.field',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
                    'custom_field.type.provider',
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
                    'form.factory',
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
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
                    'session',
                    'form.factory',
                    'translator',
                    'mautic.custom.model.object',
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
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
                    'custom_object.route.provider',
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
            'mautic.custom.model.field.value' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_field.type.provider',
                ],
            ],
            'mautic.custom.model.item' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomItemModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_item.repository',
                    'custom_item.permission.provider',
                    'mautic.helper.user',
                    'mautic.custom.model.field',
                    'mautic.custom.model.field.value',
                    'custom_field.type.provider',
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
                'class'     => \MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomField::class,
                ],
            ],
            'custom_item.repository' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomItem::class,
                ],
            ],
            'custom_object.repository' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomObject::class,
                ],
            ],
        ],
        'events' => [
            'custom_object.assets.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\AssetsSubscriber::class,
                'arguments' => [
                    'templating.helper.assets',
                    'mautic.helper.core_parameters',
                ]
            ],
            'custom_object.menu.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\MenuSubscriber::class,
                'arguments' => [
                    'mautic.custom.model.object',
                    'mautic.helper.core_parameters',
                ],
            ],
            'custom_object.tab.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\TabSubscriber::class,
                'arguments' => [
                    'mautic.custom.model.object',
                    'mautic.custom.model.item',
                    'mautic.helper.core_parameters',
                ],
            ],
            'custom_field.type.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldTypeSubscriber::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'custom_field.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldSubscriber::class,
                'arguments' => [
                    'custom_field.type.provider',
                ],
                'tag' => 'doctrine.event_listener',
                'tagArguments' => [
                    'event' => 'postLoad',
                    'lazy' => true,
                ]
            ],
            'custom_field.button.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldButtonSubscriber::class,
                'arguments' => [
                    'custom_field.permission.provider',
                    'custom_field.route.provider',
                ],
            ],
            'custom_item.button.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomItemButtonSubscriber::class,
                'arguments' => [
                    'custom_item.permission.provider',
                    'custom_item.route.provider',
                ],
            ],
            'custom_object.button.subscriber' => [
                'class' => \MauticPlugin\CustomObjectsBundle\EventListener\CustomObjectButtonSubscriber::class,
                'arguments' => [
                    'custom_object.permission.provider',
                    'custom_object.route.provider',
                ],
            ],
        ],
        'forms' => [
            'custom_item.item.form' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Form\Type\CustomItemType::class,
            ],
            'custom_field.field.form' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldType::class,
                'arguments' => [
                    'custom_object.repository'
                ],
                'tag' => 'form.type'
            ],
            'custom_field.field.value.form' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Form\Type\CustomFieldValueType::class,
                'arguments' => [
                    'custom_field.type.provider',
                ],
            ],
        ],
        'commands' => [
            'custom_object.command.generate_sample_data' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Command\GenerateSampleDataCommand::class,
                'arguments' => [
                    'mautic.custom.model.object',
                    'mautic.custom.model.item',
                    'mautic.lead.model.lead',
                    'doctrine.orm.entity_manager',
                    'custom_object.random.helper',
                ],
                'tag' => 'console.command',
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
            'custom_field.route.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider::class,
                'arguments' => [
                    'router',
                ],
            ],
            'custom_item.route.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider::class,
                'arguments' => [
                    'router',
                ],
            ],
            'custom_item.permission.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'custom_object.route.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider::class,
                'arguments' => [
                    'router',
                ],
            ],
            'custom_object.permission.provider' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'custom_object.random.helper' => [
                'class' => \MauticPlugin\CustomObjectsBundle\Helper\RandomHelper::class,
            ],
            'custom_object.custom_field_factory' => [
                'class' =>\MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory::class,
                'arguments' => [
                    'custom_field.type.provider'
                ]
            ]
        ],
    ],
    'parameters' => [
        'custom_objects_enabled' => true,
    ],
];
