<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\DTO;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

class CustomItemFieldListData
{
    /**
     * @var array
     */
    private $columns;

    /**
     * @var array
     */
    private $data;

    public function __construct(array $columns, array $data)
    {
        $this->columns = $columns;
        $this->data    = $data;
    }

    public function getColumnLabels(): array
    {
        return $this->columns;
    }

    /**
     * @return CustomFieldValueInterface[]
     */
    public function getFields(int $itemId): array
    {
        return $this->data[$itemId];
    }
}
