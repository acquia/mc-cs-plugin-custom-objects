<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCustomFieldType implements CustomFieldTypeInterface
{
    /**
     * @var string
     */
    protected $key = 'undefined';

    /**
     * @var mixed[]
     */
    protected $formTypeOptions = [];

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FilterOperatorProviderInterface
     */
    protected $filterOperatorProvider;

    public function __construct(
        TranslatorInterface $translator,
        FilterOperatorProviderInterface $filterOperatorProvider
    ) {
        $this->translator             = $translator;
        $this->filterOperatorProvider = $filterOperatorProvider;
    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function getName(): string
    {
        return $this->translator->trans(static::NAME);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTableAlias(): string
    {
        return 'cfv_'.$this->getKey();
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        return $this->filterOperatorProvider->getAllOperators();
    }

    public function getTableName(): string
    {
        return static::TABLE_NAME;
    }

    public function getPrefixedTableName(): string
    {
        return MAUTIC_TABLE_PREFIX.static::TABLE_NAME;
    }

    /**
     * @return string[]
     */
    public function getOperatorOptions(): array
    {
        $operators = $this->getOperators();
        $options   = [];

        foreach ($operators as $key => $operator) {
            $options[$key] = $this->translator->trans($operator['label']);
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function createFormTypeOptions(array $options = []): array
    {
        return array_merge_recursive($this->formTypeOptions, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChoices(): bool
    {
        $type = $this->getSymfonyFormFieldType();

        return ChoiceType::class === $type ||
            is_subclass_of($this->getSymfonyFormFieldType(), ChoiceType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue(CustomField $customField, $value): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function usePlaceholder(): bool
    {
        return $this->hasChoices() && (!$this instanceof AbstractMultivalueType);
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaultValueTransformer(): DataTransformerInterface
    {
        throw new UndefinedTransformerException();
    }

    /**
     * {@inheritdoc}
     */
    public function createApiValueTransformer(): DataTransformerInterface
    {
        throw new UndefinedTransformerException();
    }

    /**
     * {@inheritdoc}
     */
    public function createViewTransformer(): DataTransformerInterface
    {
        throw new UndefinedTransformerException();
    }

    /**
     * @param mixed $value
     *
     * @throws \UnexpectedValueException
     */
    public function validateRequired(CustomField $customField, $value): void
    {
        if (!$customField->isRequired()) {
            return;
        }

        $valueIsEmpty = false === $value || (empty($value) && '0' !== $value && 0 !== $value);

        if (!$valueIsEmpty) {
            return;
        }

        throw new \UnexpectedValueException($this->translator->trans('custom.field.required', ['%fieldName%' => "{$customField->getLabel()} ({$customField->getAlias()})"], 'validators'));
    }

    /**
     * {@inheritdoc}
     */
    public function valueToString(CustomFieldValueInterface $fieldValue): string
    {
        return (string) $fieldValue->getValue();
    }
}
