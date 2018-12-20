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

namespace MauticPlugin\CustomObjectsBundle;

/**
 * Events available for Custom Fields.
 */
final class CustomFieldEvents
{
    /**
     * The custom_fields.make_type_list event is dispatched during the list of field types is being built.
     *
     * The event listener receives a
     * MauticPlugins\CustomObjectsBundle\Event\CustomFieldTypeEvent instance.
     *
     * @var string
     */
    const MAKE_FIELD_TYPE_LIST = 'custom_fields.make_type_list';
}
