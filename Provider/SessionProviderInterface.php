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

    public function getPage(int $default = 1): int;

    public function setPage(int $page): void;

    public function getPageLimit(): int;

    public function setPageLimit(int $pageLimit): void;

    public function getOrderBy(string $default): string;

    public function setOrderBy(string $orderBy): void;

    public function getOrderByDir(string $default = 'DESC'): string;

    public function setOrderByDir(string $orderByDir): void;

    public function getFilter(string $default = ''): string;

    public function setFilter(string $filter): void;
}
