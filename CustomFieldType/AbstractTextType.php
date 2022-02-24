<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

abstract class AbstractTextType extends AbstractCustomFieldType
{
    public const TABLE_NAME = 'custom_field_value_text';

    /**
     * @param mixed|null $value
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueText($customField, $customItem, (string) $value);
    }

    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextType::class;
    }

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
        $allowedOperators = array_flip(['=', '!=', 'empty', '!empty', 'like', '!like', 'startsWith', 'endsWith', 'contains']);

        return array_intersect_key($allOperators, $allowedOperators);
    }
}
