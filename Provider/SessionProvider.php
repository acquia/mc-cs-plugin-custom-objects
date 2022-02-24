<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Symfony\Component\HttpFoundation\Session\Session;

class SessionProvider
{
    /**
     * @var string
     */
    private const KEY_PAGE = 'page';

    /**
     * @var string
     */
    private const KEY_LIMIT = 'limit';

    /**
     * @var string
     */
    private const KEY_ORDER_BY = 'orderby';

    /**
     * @var string
     */
    private const KEY_ORDER_BY_DIR = 'orderbydir';

    /**
     * @var string
     */
    private const KEY_FILTER = 'filter';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var int
     */
    private $defaultPageLimit;

    /**
     * @var string
     */
    private $namespace;

    public function __construct(Session $session, string $namespace, int $defaultPageLimit)
    {
        $this->session          = $session;
        $this->namespace        = $namespace;
        $this->defaultPageLimit = $defaultPageLimit;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getPage(int $default = 1): int
    {
        return (int) $this->getValue(static::KEY_PAGE, $default);
    }

    public function getPageLimit(): int
    {
        return (int) $this->getValue(static::KEY_LIMIT, $this->defaultPageLimit);
    }

    public function getOrderBy(string $default): string
    {
        return $this->getValue(static::KEY_ORDER_BY, $default);
    }

    public function getOrderByDir(string $default = 'DESC'): string
    {
        return $this->getValue(static::KEY_ORDER_BY_DIR, $default);
    }

    public function getFilter(string $default = ''): string
    {
        return $this->getValue(static::KEY_FILTER, $default);
    }

    public function setPage(int $page): void
    {
        $this->setValue(static::KEY_PAGE, $page);
    }

    public function setPageLimit(int $pageLimit): void
    {
        $this->setValue(static::KEY_LIMIT, $pageLimit);
    }

    public function setOrderBy(string $orderBy): void
    {
        $this->setValue(static::KEY_ORDER_BY, $orderBy);
    }

    public function setOrderByDir(string $orderByDir): void
    {
        $this->setValue(static::KEY_ORDER_BY_DIR, $orderByDir);
    }

    public function setFilter(string $filter): void
    {
        $this->setValue(static::KEY_FILTER, $filter);
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    private function getValue(string $key, $default)
    {
        return $this->session->get($this->buildName($key), $default);
    }

    private function setValue(string $key, $value): void
    {
        $this->session->set($this->buildName($key), $value);
    }

    private function buildName(string $key): string
    {
        return 'mautic.'.$this->namespace.'.'.$key;
    }
}
