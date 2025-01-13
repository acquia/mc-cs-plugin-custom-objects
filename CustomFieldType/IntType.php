<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class IntType extends AbstractCustomFieldType
{
    /**
     * @var string
     */
    public const NAME = 'custom.field.type.int';

    public const TABLE_NAME = 'custom_field_value_int';

    /**
     * @var string
     */
    protected $key = 'int';

    public function getSymfonyFormFieldType(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\NumberType::class;
    }

    public function getEntityClass(): string
    {
        return CustomFieldValueInt::class;
    }

    /**
     * @param mixed|null $value
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueInt($customField, $customItem, (int) $value);
    }

    /**
     * Remove operators that are supported only by segment filters.
     *
     * @return string[]
     */
    public function getOperatorOptions(): array
    {
        $options = parent::getOperatorOptions();

        unset($options['between'], $options['!between']);

        return $options;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        $allOperators     = parent::getOperators();
        $allowedOperators = array_flip(['=', '!=', 'gt', 'gte', 'lt', 'lte', 'empty', '!empty', 'between', '!between']);

        return array_intersect_key($allOperators, $allowedOperators);
    }
}
