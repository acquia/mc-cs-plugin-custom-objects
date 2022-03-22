<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\DateTransformer;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\Form\DataTransformerInterface;

interface CustomFieldTypeInterface
{
    public const TABLE_NAME = 'undefined';

    public const NAME = 'undefined';

    public function getName(): string;

    public function getKey(): string;

    /**
     * Symfony form field representing this custom field.
     */
    public function getSymfonyFormFieldType(): string;

    /**
     * Has this field multiple choicesS?
     */
    public function hasChoices(): bool;

    public function getEntityClass(): string;

    /**
     * @param mixed|null $value
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface;

    /**
     * @param mixed $value
     *
     * @throws \UnexpectedValueException
     */
    public function validateValue(CustomField $customField, $value): void;

    /**
     * @param mixed $value
     *
     * @throws \UnexpectedValueException
     */
    public function validateRequired(CustomField $customField, $value): void;

    public function getTableName(): string;

    public function getPrefixedTableName(): string;

    public function getTableAlias(): string;

    /**
     * @return mixed[]
     */
    public function getOperators(): array;

    /**
     * @return mixed[]
     */
    public function getOperatorOptions(): array;

    /**
     * @param mixed[] $options
     *
     * @return mixed[]
     */
    public function createFormTypeOptions(array $options = []): array;

    /**
     * Using placeholder for form field is needed.
     */
    public function usePlaceholder(): bool;

    /**
     * Create transformer for transformation default value type from string to custom type.
     *
     * @see CustomField::getDefaultValue()
     * @see DateTransformer
     */
    public function createDefaultValueTransformer(): DataTransformerInterface;

    /**
     * Transformer used for API requests and responses.
     */
    public function createApiValueTransformer(): DataTransformerInterface;

    public function createViewTransformer(): DataTransformerInterface;

    /**
     * @param mixed $fieldValue
     */
    public function valueToString(CustomFieldValueInterface $fieldValue): string;
}
