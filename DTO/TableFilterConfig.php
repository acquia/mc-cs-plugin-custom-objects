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

class TableFilterConfig
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $expression;

    /**
     * @param string $entityName
     * @param string $columnName
     * @param mixed  $value
     * @param string $expression
     */
    public function __construct(string $entityName, string $columnName, $value, string $expression = 'eq')
    {
        $this->entityName = $entityName;
        $this->columnName = $columnName;
        $this->value      = $value;
        $this->expression = $expression;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getFullColumnName(): string
    {
        return "{$this->getTableAlias()}.{$this->getColumnName()}";
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        $path = explode('\\', $this->entityName);

        return end($path);
    }
}
