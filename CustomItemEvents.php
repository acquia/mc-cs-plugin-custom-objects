<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_PRE_SAVE = 'custom.item.on_pre_save';

    /**
     * The custom.item.on_post_save event is fired when a custom item is saved.
     *
     * The event listener receives a
     * MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_POST_SAVE = 'custom.item.on_post_save';

    /**
     * The custom.item.on_pre_delete event is fired when a custom item is about to be deleted.
     *
     * The event listener receives a
     * MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_PRE_DELETE = 'custom.item.on_pre_delete';

    /**
     * The custom.item.on_post_delete event is fired when a custom item is deleted.
     *
     * The event listener receives a
     * MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent
     *
     * @var string
     */
    public const ON_CUSTOM_ITEM_POST_DELETE = 'custom.item.on_post_delete';

    /**
     * The custom.item.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_ACTION = 'custom.item.on_campaign_trigger_action';

    /**
     * The custom.item.on_campaign_trigger_condition event is fired when the campaign condition triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_CONDITION = 'custom.item.on_campaign_trigger_notification';
}
