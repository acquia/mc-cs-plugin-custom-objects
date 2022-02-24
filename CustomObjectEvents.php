<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle;

/**
 * Events available for Custom Objects.
 */
final class CustomObjectEvents
{
    /**
     * The custom.object.on_pre_save event is fired when a custom object is about to be saved.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent
     *
     * @var string
     */
    public const ON_CUSTOM_OBJECT_PRE_SAVE = 'custom.object.on_pre_save';

    /**
     * The custom.object.on_post_save event is fired when a custom object is saved.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent
     *
     * @var string
     */
    public const ON_CUSTOM_OBJECT_POST_SAVE = 'custom.object.on_post_save';

    /**
     * The custom.object.on_pre_delete event is fired when a custom object is about to be deleted.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent
     *
     * @var string
     */
    public const ON_CUSTOM_OBJECT_PRE_DELETE = 'custom.object.on_pre_delete';

    /**
     * The custom.object.ui.pre_delete event is fired when a user initiates custom object deletion.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent
     *
     * @var string
     */
    public const ON_CUSTOM_OBJECT_USER_PRE_DELETE = 'custom.object.ui.pre_delete';

    /**
     * The custom.object.on_post_delete event is fired when a custom object is deleted.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent
     *
     * @var string
     */
    public const ON_CUSTOM_OBJECT_POST_DELETE = 'custom.object.on_post_delete';

    /**
     * The custom.object.list.format event is fired when a list of custom object values needs to be formatted.
     *
     * The event listener receives a
     *
     * @see \MauticPlugin\CustomObjectsBundle\Event\CustomObjectListFormatEvent
     *
     * @var string
     */
    public const ON_CUSTOM_OBJECT_LIST_FORMAT = 'custom.object.list.format';
}
