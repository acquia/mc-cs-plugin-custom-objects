<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
