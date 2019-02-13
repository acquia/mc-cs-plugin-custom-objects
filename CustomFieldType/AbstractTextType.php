<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;

abstract class AbstractTextType extends AbstractCustomFieldType
{
    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return CustomFieldValueText::class;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return MAUTIC_TABLE_PREFIX.'custom_field_value_text';
    }

    /**
     * @return array
     */
    public function getOperators(): array
    {
        $allOperators = parent::getOperators();
        $allowedOperators = array_flip(['=', '!=', 'empty', '!empty', 'like', '!like', 'in', '!in', 'startsWith', 'endsWith', 'contains']);

        return array_intersect_key($allOperators, $allowedOperators);
    }
}