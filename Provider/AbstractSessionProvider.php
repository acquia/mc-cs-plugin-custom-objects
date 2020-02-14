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
use Symfony\Component\HttpFoundation\Session\Session;

abstract class AbstractSessionProvider implements SessionProviderInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(Session $session, CoreParametersHelper $coreParametersHelper)
    {
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function getPage(int $default = 1): int
    {
        return (int) $this->session->get(static::KEY_PAGE, $default);
    }

    public function getPageLimit(): int
    {
        $defaultlimit = (int) $this->coreParametersHelper->get('default_pagelimit');

        return (int) $this->session->get(static::KEY_LIMIT, $defaultlimit);
    }

    public function getOrderBy(string $default): string
    {
        return $this->session->get(static::KEY_ORDER_BY, $default);
    }

    public function getOrderByDir(string $default = 'DESC'): string
    {
        return $this->session->get(static::KEY_ORDER_BY_DIR, $default);
    }

    public function getFilter(string $default = ''): string
    {
        return $this->session->get(static::KEY_FILTER, $default);
    }

    public function setPage(int $page): void
    {
        $this->session->set(static::KEY_PAGE, $page);
    }

    public function setPageLimit(int $pageLimit): void
    {
        $this->session->set(static::KEY_LIMIT, $pageLimit);
    }

    public function setOrderBy(string $orderBy): void
    {
        $this->session->set(static::KEY_ORDER_BY, $orderBy);
    }

    public function setOrderByDir(string $orderByDir): void
    {
        $this->session->set(static::KEY_ORDER_BY_DIR, $orderByDir);
    }

    public function setFilter(string $filter): void
    {
        $this->session->set(static::KEY_FILTER, $filter);
    }
}
