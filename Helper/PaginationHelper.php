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

namespace MauticPlugin\CustomObjectsBundle\Helper;

class PaginationHelper
{
    /**
     * @deprecated Use TableConfig instead
     *
     * @param int $page
     * @param int $limit
     *
     * @return int
     */
    public static function countOffset(int $page, int $limit): int
    {
        $offset = 1 === $page ? 0 : (($page - 1) * $limit);

        return $offset < 0 ? 0 : $offset;
    }
}
