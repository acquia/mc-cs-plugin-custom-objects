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

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

/**
 * Use this for choice fields with static choice list like the country field.
 */
interface StaticChoiceTypeInterface
{
    /**
     * @return string[]
     */
    public function getChoices(): array;
}
