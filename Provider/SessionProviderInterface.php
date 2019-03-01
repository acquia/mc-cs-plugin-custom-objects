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

    public const KEY_LIMIT = 'undefined';

    public const KEY_ORDER_BY = 'undefined';

    public const KEY_ORDER_BY_DIR = 'undefined';

    public const KEY_FILTER = 'undefined';

    /**
     * @param int $default
     *
     * @return int
     */
    public function getPage(int $default = 1): int;

    /**
     * @param int $page
     */
    public function setPage(int $page): void;

    /**
     * @return int
     */
    public function getPageLimit(): int;

    /**
     * @param int $pageLimit
     */
    public function setPageLimit(int $pageLimit): void;

    /**
     * @param string $default
     *
     * @return string
     */
    public function getOrderBy(string $default): string;

    /**
     * @param string $orderBy
     */
    public function setOrderBy(string $orderBy): void;

    /**
     * @param string $default
     *
     * @return string
     */
    public function getOrderByDir(string $default): string;

    /**
     * @param string $orderByDir
     */
    public function setOrderByDir(string $orderByDir = 'DESC'): void;

    /**
     * @param string $default
     *
     * @return string
     */
    public function getFilter(string $default = ''): string;

    /**
     * @param string $filter
     */
    public function setFilter(string $filter): void;
}
