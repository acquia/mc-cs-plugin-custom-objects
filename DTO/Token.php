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

namespace MauticPlugin\CustomObjectsBundle\DTO;

/**
 * Object that represents parsed token like this one:.
 *
 * {custom-object=product:sku | where=segment-filter |order=latest|limit=1 | default=Nothing to see here}
 */
class Token
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $limit = 1;

    /**
     * @var string
     */
    private $where = '';

    /**
     * @var string
     */
    private $order = 'latest';

    /**
     * @var string
     */
    private $defaultValue = '';

    /**
     * @var string
     */
    private $customFieldAlias;

    /**
     * @var string
     */
    private $customObjectAlias;

    /**
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * @param string $defaultValue
     */
    public function setDefaultValue(string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getWhere(): string
    {
        return $this->where;
    }

    /**
     * @param string $where
     */
    public function setWhere(string $where): void
    {
        $this->where = $where;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getCustomFieldAlias(): string
    {
        return $this->customFieldAlias;
    }

    /**
     * @param string $customFieldAlias
     */
    public function setCustomFieldAlias(string $customFieldAlias): void
    {
        $this->customFieldAlias = $customFieldAlias;
    }

    /**
     * @return string
     */
    public function getCustomObjectAlias(): string
    {
        return $this->customObjectAlias;
    }

    /**
     * @param string $customObjectAlias
     */
    public function setCustomObjectAlias(string $customObjectAlias): void
    {
        $this->customObjectAlias = $customObjectAlias;
    }
}
