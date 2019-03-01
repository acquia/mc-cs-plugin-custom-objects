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

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

abstract class AbstractTextType extends AbstractCustomFieldType
{
    public const TABLE_NAME = 'custom_field_value_text';

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param mixed|null  $value
     *
     * @return CustomFieldValueInterface
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueText($customField, $customItem, (string) $value);
    }

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextType::class;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return CustomFieldValueText::class;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        $allOperators     = parent::getOperators();
        $allowedOperators = array_flip(['=', '!=', 'empty', '!empty', 'like', '!like', 'in', '!in', 'startsWith', 'endsWith', 'contains']);

        return array_intersect_key($allOperators, $allowedOperators);
    }
}
