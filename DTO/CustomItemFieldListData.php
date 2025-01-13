<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\DTO;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

class CustomItemFieldListData
{
    public function __construct(private array $columns, private array $data)
    {
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
