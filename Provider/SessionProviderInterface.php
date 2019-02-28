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

interface SessionProviderInterface
{
    public const KEY_PAGE = 'undefined';

    /**
     * @param int $default
     * 
     * @return int
     */
    public function getPage(int $default = 1): int;
}
