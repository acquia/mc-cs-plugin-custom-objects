<?php

declare(strict_types=1);

use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemRouteProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectRouteProvider;

$coParams = [
    'name'        => 'Custom Objects',
    'description' => 'Adds custom objects and fields features to Mautic',
    'version'     => '0.0.27',
    'author'      => 'Mautic, Inc.',

    'routes' => [
        'main' => [
            // Custom Fields
            CustomFieldRouteProvider::ROUTE_FORM => [
                'path'       => '/custom/field/edit',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomField\FormController::renderFormAction',
                'method'     => 'GET',
            ],
            CustomFieldRouteProvider::ROUTE_SAVE => [
                'path'       => '/custom/field/save/{fieldType}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomField\SaveController::saveAction',
                'method'     => 'POST',
                'defaults'   => [
                    'fieldId' => null,
                ],
            ],

            // Custom Items
            CustomItemRouteProvider::ROUTE_LIST => [
                'path'       => '/custom/object/{objectId}/item/{page}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ListController::listAction',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            CustomItemRouteProvider::ROUTE_VIEW => [
                'path'       => '/custom/object/{objectId}/item/view/{itemId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ViewController::viewAction',
                'method'     => 'GET|POST',
            ],
            CustomItemRouteProvider::ROUTE_NEW => [
                'path'       => '/custom/object/{objectId}/item/new',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController::newAction',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_NEW_REDIRECT_TO_CONTACT => [
                'path'       => '/custom/object/{objectId}/contact/{contactId}/item/new',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController::newWithRedirectToContactAction',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_EDIT => [
                'path'       => '/custom/object/{objectId}/item/edit/{itemId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController::editAction',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_EDIT_REDIRECT_TO_CONTACT => [
                'path'       => '/custom/object/{objectId}/item/edit/{itemId}/contact/{contactId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController::editWithRedirectToContactAction',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_CLONE => [
                'path'       => '/custom/object/{objectId}/item/clone/{itemId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\FormController::cloneAction',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_CANCEL => [
                'path'       => '/custom/object/{objectId}/item/cancel/{itemId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\CancelController::cancelAction',
//                'method'     => 'POST',
                'defaults'   => [
                    'itemId' => null,
                ],
            ],
            CustomItemRouteProvider::ROUTE_SAVE => [
                'path'       => '/custom/object/{objectId}/item/save/{itemId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\SaveController::saveAction',
                'method'     => 'POST',
                'defaults'   => [
                    'itemId' => null,
                ],
            ],
            CustomItemRouteProvider::ROUTE_DELETE => [
                'path'       => '/custom/object/{objectId}/item/delete/{itemId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\DeleteController::deleteAction',
                'method'     => 'GET|POST',
            ],
            CustomItemRouteProvider::ROUTE_BATCH_DELETE => [
                'path'       => '/custom/object/{objectId}/item/batch/delete',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\BatchDeleteController::deleteAction',
                'method'     => 'POST',
            ],
            CustomItemRouteProvider::ROUTE_LOOKUP => [
                'path'       => '/custom/object/{objectId}/item/lookup.json',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LookupController::listAction',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_LINK => [
                'path'       => '/custom/item/{itemId}/link/{entityType}/{entityId}.json',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LinkController::saveAction',
                'method'     => 'POST',
            ],
            CustomItemRouteProvider::ROUTE_LINK_FORM => [
                'path'       => '/custom/item/{itemId}/link-form/{entityType}/{entityId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LinkFormController::formAction',
                'method'     => 'GET',
            ],
            CustomItemRouteProvider::ROUTE_LINK_FORM_SAVE => [
                'path'       => '/custom/item/{itemId}/link-form/{entityType}/{entityId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\LinkFormController::saveAction',
                'method'     => 'POST',
            ],
            CustomItemRouteProvider::ROUTE_UNLINK => [
                'path'       => '/custom/item/{itemId}/unlink/{entityType}/{entityId}.json',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\UnlinkController::saveAction',
                'method'     => 'POST',
            ],
            CustomItemRouteProvider::ROUTE_CONTACT_LIST => [
                'path'       => '/custom/item/{objectId}/contact/{page}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ContactListController::listAction',
            ],
            CustomItemRouteProvider::ROUTE_EXPORT_ACTION => [
                'path'       => 'custom/object/{object}/export',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ExportController::exportAction',
                'method'     => 'POST',
            ],
            CustomItemRouteProvider::ROUTE_EXPORT_DOWNLOAD_ACTION => [
                'path'       => '/custom/item/export/download/{fileName}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomItem\ExportController::downloadExportAction',
            ],

            // Custom Objects
            CustomObjectRouteProvider::ROUTE_LIST => [
                'path'       => '/custom/object/{page}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ListController::listAction',
                'method'     => 'GET|POST',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_VIEW => [
                'path'       => '/custom/object/view/{objectId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\ViewController::viewAction',
                'method'     => 'GET|POST',
            ],
            CustomObjectRouteProvider::ROUTE_NEW => [
                'path'       => '/custom/object/new',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\FormController::newAction',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_EDIT => [
                'path'       => '/custom/object/edit/{objectId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\FormController::editAction',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_CLONE => [
                'path'       => '/custom/object/clone/{objectId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\FormController::cloneAction',
                'method'     => 'GET',
            ],
            CustomObjectRouteProvider::ROUTE_CANCEL => [
                'path'       => '/custom/object/cancel/{objectId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\CancelController::cancelAction',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_SAVE => [
                'path'       => '/custom/object/save/{objectId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\SaveController::saveAction',
                'method'     => 'POST',
                'defaults'   => [
                    'objectId' => null,
                ],
            ],
            CustomObjectRouteProvider::ROUTE_DELETE => [
                'path'       => '/custom/object/delete/{objectId}',
                'controller' => 'MauticPlugin\CustomObjectsBundle\Controller\CustomObject\DeleteController::deleteAction',
                'method'     => 'GET|POST',
            ],
        ],
    ],

    'services' => [
        'models' => [
            'mautic.custom.model.field' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel::class,
                'arguments' => [
                    'custom_field.repository',
                    'custom_field.permission.provider',
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.custom.model.field.value' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'validator',
                ],
            ],
            'mautic.custom.model.item' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomItemModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_item.repository',
                    'custom_item.permission.provider',
                    'mautic.custom.model.field.value',
                    'validator',
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.custom.model.import.item' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.custom.model.item',
                    'mautic.helper.twig.formatter',
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.custom.model.import.xref.contact' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'translator',
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.custom.model.field.option' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomFieldOptionModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.custom.model.object' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_object.repository',
                    'custom_object.permission.provider',
                    'mautic.custom.model.field',
//                    'mautic.lead.model.list',
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger',
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.custom.model.export_scheduler' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Model\CustomItemExportSchedulerModel::class,
                'arguments' => [
                    'mautic.helper.export',
                    'mautic.helper.mailer',
                    'mautic.custom.model.field.value',
                    'custom_item.route.provider',
                    'custom_item.xref.contact.repository',
                    'custom_item.repository',
                    'doctrine.orm.entity_manager',
                    'mautic.security',
                    'event_dispatcher',
                    'router',
                    'translator',
                    'mautic.helper.user',
                    'monolog.logger',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'permissions' => [
            'custom_object.permissions' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Security\Permissions\CustomObjectPermissions::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.custom.model.object',
                    'custom_object.config.provider',
                    'translator',
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
            'custom_item.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomItem::class,
                ],
            ],
            'custom_object.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomObject::class,
                ],
            ],
            'custom_item.xref.contact.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact::class,
                ],
            ],
            'custom_item.xref.custom_item.repository' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem::class,
                ],
            ],
            'custom_object.segment_decorator_multiselect' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Segment\Decorator\MultiselectDecorator::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_operator',
                    'mautic.lead.repository.lead_segment_filter_descriptor',
                ],
            ],
        ],
        'events' => [
            'custom_field.post_load.subscriber' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldPostLoadSubscriber::class,
                'arguments' => [
                    'custom_field.type.provider',
                ],
                'tag'          => 'doctrine.event_listener',
                'tagArguments' => [
                    'event' => 'postLoad',
                    'lazy'  => true,
                ],
            ],
            // There's a problem with multiple tags and arguments definition using array.
            // So subscriber above should contain subscriber method below. But it is not possible now.
            'custom_field.pre_save.subscriber' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\EventListener\CustomFieldPreSaveSubscriber::class,
                'arguments' => [
                    'mautic.custom.model.field.option',
                ],
            ],
            'custom_item.campaign.subscriber' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.custom.model.field',
                    'mautic.custom.model.object',
                    'mautic.custom.model.item',
                    'translator',
                    'custom_object.config.provider',
                    'custom_object.query.filter.helper',
                    'custom_object.query.filter.factory',
                    'database_connection',
                ],
            ],
            'custom_object.serializer.subscriber' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\EventListener\SerializerSubscriber::class,
                'arguments' => [
                    'custom_object.config.provider',
                    'custom_item.xref.contact.repository',
                    'mautic.custom.model.item',
                    'request_stack',
                ],
                'tag'          => 'jms_serializer.event_subscriber',
                'tagArguments' => [
                    'event' => \JMS\Serializer\EventDispatcher\Events::POST_SERIALIZE,
                ],
            ],
            'custom_object.emailtoken.subscriber' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\EventListener\TokenSubscriber::class,
                'arguments' => [
                    'custom_object.config.provider',
                    'custom_object.query.filter.helper',
                    'custom_object.query.filter.factory',
                    'mautic.custom.model.object',
                    'mautic.custom.model.item',
                    'custom_object.token.parser',
                    'mautic.campaign.model.event',
                    'event_dispatcher',
                    'custom_object.helper.token_formatter',
                ],
            ],
        ],
        'forms' => [
            'custom_field.field.params.to.string.transformer' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Form\DataTransformer\ParamsToStringTransformer::class,
                'arguments' => [
                    'jms_serializer',
                ],
            ],
            'custom_field.field.options.to.string.transformer' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Form\DataTransformer\OptionsToStringTransformer::class,
                'arguments' => [
                    'jms_serializer',
                    'mautic.custom.model.field',
                ],
            ],
        ],
        'fieldTypes' => [
            'custom.field.type.country' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\CountryType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.date' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\DateType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.datetime' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.email' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\EmailType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.hidden' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\HiddenType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.int' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.phone' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\PhoneType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.select' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.multiselect' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator', 'custom_object.csv.helper'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.text' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.textarea' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\TextareaType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            'custom.field.type.url' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\UrlType::class,
                'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
                'tag'       => 'custom.field.type',
            ],
            // Hiding these as they duplicate the select and multiselect field type functionality.
            // Remove these field types if no one will miss them.
            // 'custom.field.type.checkbox_group' => [
            //     'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\CheckboxGroupType::class,
            //     'arguments' => ['translator', 'mautic.lead.provider.fillterOperator', 'custom_object.csv.helper'],
            //     'tag'       => 'custom.field.type',
            // ],
            // 'custom.field.type.radio_group' => [
            //     'class'     => \MauticPlugin\CustomObjectsBundle\CustomFieldType\RadioGroupType::class,
            //     'arguments' => ['translator', 'mautic.lead.provider.fillterOperator'],
            //     'tag'       => 'custom.field.type',
            // ],
        ],
        'other' => [
            'custom_object.config.provider' => [
                'class'     => ConfigProvider::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'custom_field.type.provider' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'custom_field.permission.provider' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'custom_field.route.provider' => [
                'class'     => CustomFieldRouteProvider::class,
                'arguments' => [
                    'router',
                ],
            ],
            'custom_item.route.provider' => [
                'class'     => CustomItemRouteProvider::class,
                'arguments' => [
                    'router',
                ],
            ],
            'custom_item.permission.provider' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'custom_object.session.provider_factory' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Provider\SessionProviderFactory::class,
                'arguments' => [
                    'session',
                    'mautic.helper.core_parameters',
                ],
            ],
            'custom_object.route.provider' => [
                'class'     => CustomObjectRouteProvider::class,
                'arguments' => [
                    'router',
                ],
            ],
            'custom_object.permission.provider'            => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider::class,
                'arguments' => [
                    'mautic.security',
                ],
            ],
            'custom_object.lock_flash_message.helper' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Helper\LockFlashMessageHelper::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'translator',
                    'mautic.core.service.flashbag',
                    'router',
                ],
            ],
            'custom_object.csv.helper'                  => [
                'class' => \MauticPlugin\CustomObjectsBundle\Helper\CsvHelper::class,
            ],
            'custom_object.token.parser'                  => [
                'class' => \MauticPlugin\CustomObjectsBundle\Helper\TokenParser::class,
            ],
            'custom_object.random.helper'                  => [
                'class' => \MauticPlugin\CustomObjectsBundle\Helper\RandomHelper::class,
            ],
            'custom_object.custom_field_factory'           => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Entity\CustomFieldFactory::class,
                'arguments' => ['custom_field.type.provider'],
            ],
            'mautic.lead.query.builder.custom_field.value' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomFieldFilterQueryBuilder::class,
                'arguments' => [
                    'mautic.lead.model.random_parameter_name',
                    'event_dispatcher',
                    'custom_object.query.filter.helper',
                ],
            ],
            'mautic.lead.query.builder.custom_item.value'  => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\CustomItemNameFilterQueryBuilder::class,
                'arguments' => [
                    'mautic.lead.model.random_parameter_name',
                    'custom_object.query.filter.helper',
                    'event_dispatcher',
                ],
            ],
            'custom_object.query.filter.factory' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Segment\Query\Filter\QueryFilterFactory::class,
                'arguments' => [
                    'mautic.lead.model.lead_segment_filter_factory',
                    'custom_object.query.filter.helper',
                ],
            ],
            'query_filter_factory_calculator' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory\Calculator::class,
            ],
            'query_filter_factory' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Helper\QueryFilterFactory::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'custom_field.type.provider',
                    'custom_field.repository',
                    'query_filter_factory_calculator',
                    '%mautic.'.ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT.'%',
                ],
            ],
            'custom_object.query.filter.helper'            => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'query_filter_factory',
                ],
            ],
            'custom_object.helper.token_formatter' => [
                'class'     => \MauticPlugin\CustomObjectsBundle\Helper\TokenFormatter::class,
            ],
        ],
    ],
    'parameters' => [
        ConfigProvider::CONFIG_PARAM_ENABLED                              => true,
        ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT => 3,
        'custom_item_export_dir'                                          => '%kernel.project_dir%/media/files/temp',
    ],
];

if (interface_exists('ApiPlatform\\Core\\Api\\IriConverterInterface')) {
    $coParams['services']['other']['api_platform.custom_object.serializer.api_normalizer_jsonld'] = [
        'class'            => \MauticPlugin\CustomObjectsBundle\Serializer\ApiNormalizer::class,
        'decoratedService' => ['api_platform.jsonld.normalizer.item', 'api_platform.jsonld.normalizer.item.inner'],
        'arguments'        => [
            'api_platform.jsonld.normalizer.item.inner',
            'custom_field.type.provider',
            'mautic.custom.model.item',
            'api_platform.iri_converter',
            'doctrine.orm.entity_manager',
        ],
    ];
    $coParams['services']['other']['api_platform.custom_object.serializer.api_normalizer_json'] = [
        'class'            => \MauticPlugin\CustomObjectsBundle\Serializer\ApiNormalizer::class,
        'decoratedService' => ['api_platform.serializer.normalizer.item', 'api_platform.serializer.normalizer.item.inner'],
        'arguments'        => [
            'api_platform.serializer.normalizer.item.inner',
            'custom_field.type.provider',
            'mautic.custom.model.item',
            'api_platform.iri_converter',
            'doctrine.orm.entity_manager',
        ],
    ];
    $coParams['services']['other']['api_platform.custom_object.custom_item.extension'] = [
        'class'     => \MauticPlugin\CustomObjectsBundle\Extension\CustomItemListeningExtension::class,
        'arguments' => [
            'mautic.helper.user',
            'mautic.security',
        ],
        'tag' => 'api_platform.doctrine.orm.query_extension.collection',
    ];
}

return $coParams;
