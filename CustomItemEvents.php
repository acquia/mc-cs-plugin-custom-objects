<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle;

/**
 * Events available for Custom Items.
 */
final class CustomItemEvents
{
    /**
     * The custom.item.on_pre_save event is fired when a custom item is about to be saved.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_PRE_SAVE = 'custom.item.on_pre_save';

    /**
     * The custom.item.on_post_save event is fired when a custom item is saved.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_POST_SAVE = 'custom.item.on_post_save';

    /**
     * The custom.item.on_pre_delete event is fired when a custom item is about to be deleted.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_PRE_DELETE = 'custom.item.on_pre_delete';

    /**
     * The custom.item.on_post_delete event is fired when a custom item is deleted.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_POST_DELETE = 'custom.item.on_post_delete';

    /**
     * The custom.item.on_item_dbal_list_query event is fired when custom items list DBAL query is being build.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_LIST_DBAL_QUERY = 'custom.item.on_item_dbal_list_query';

    /**
     * The custom.item.on_item_orm_list_query event is fired when custom items list ORM query is being build.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_LIST_ORM_QUERY = 'custom.item.on_item_orm_list_query';

    /**
     * The custom.item.on_item_lookup_query event is fired when custom items lookup query is being build.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_LOOKUP_QUERY = 'custom.item.on_item_lookup_query';

    /**
     * The custom.item.on_link_entity_id event is fired when a custom item is about to be connected to an (at that time) unknown entity.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY = 'custom.item.on_link_entity_id';

    /**
     * The custom.item.on_link_entity event is fired when a custom item is connected to an entity.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_LINK_ENTITY = 'custom.item.on_link_entity';

    /**
     * The custom.item.on_unlink_entity event is fired when a custom item is disconnected from an entity.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_UNLINK_ENTITY = 'custom.item.on_unlink_entity';

    /**
     * The custom.item.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     *
     * @see \Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_ACTION = 'custom.item.on_campaign_trigger_action';

    /**
     * The custom.item.on_campaign_trigger_condition event is fired when the campaign condition triggers.
     *
     * The event listener receives a
     *
     * @see \Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_CONDITION = 'custom.item.on_campaign_trigger_notification';

    /**
     * The custom.item.on_custom_item_export event is fired when the custom item is being exported.
     *
     * @see MauticPlugin\CustomObjectsBundle\Event\CustomItemExportSchedulerEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_SCHEDULE_EXPORT = 'custom.item.on_custom_item_export_scheduled';

    /**
     * The custom.item.custom_item_prepare_export_file event is fired when the custom item data is being prepared to be exported.
     *
     * @see MauticPlugin\CustomObjectsBundle\Event\CustomItemExportSchedulerEvent
     *
     * @var string
     */
    public const CUSTOM_ITEM_PREPARE_EXPORT_FILE = 'custom.item.custom_item_prepare_export_file';

    public const CUSTOM_ITEM_MAIL_EXPORT_FILE = 'custom.item.custom_item_mail_export_file';

    public const POST_EXPORT_MAIL_SENT = 'custom.item.post_export_mail_sent';

    public const ON_PROCESSING_FILE = 'custom.item.on_processing_file';
}
