<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ConfigProvider
{
    /**
     * @var string
     */
    public const CONFIG_PARAM_ENABLED                              = 'custom_objects_enabled';
    public const CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT = 'custom_object_item_value_to_contact_relation_limit';

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * Returns true if the Custom Objects plugin is enabled.
     */
    public function pluginIsEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get(self::CONFIG_PARAM_ENABLED, true);
    }
}
