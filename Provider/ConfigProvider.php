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

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ConfigProvider
{
    /**
     * @var string
     */
    public const CONFIG_PARAM_ENABLED = 'custom_objects_enabled';

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @param CoreParametersHelper $coreParameters
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * Returns true if the Custom Objects plugin is enabled
     *
     * @return boolean
     */
    public function pluginIsEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->getParameter(ConfigProvider::CONFIG_PARAM_ENABLED, true);
    }
}
