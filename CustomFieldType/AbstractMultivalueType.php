<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\CustomFieldType;

use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\CsvTransformer;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\MultivalueTransformer;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractMultivalueType extends AbstractCustomFieldType
{
    public const TABLE_NAME = 'custom_field_value_option';

    /**
     * @var CsvHelper
     */
    private $csvHelper;

    public function __construct(
        TranslatorInterface $translator,
        FilterOperatorProviderInterface $filterOperatorProvider,
        CsvHelper $csvHelper
    ) {
        parent::__construct($translator, $filterOperatorProvider);

        $this->csvHelper = $csvHelper;
    }

    /**
     * @param null $value
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueOption($customField, $customItem, $value);
    }

    public function getSymfonyFormFieldType(): string
    {
        return ChoiceType::class;
    }

    public function getEntityClass(): string
    {
        return CustomFieldValueOption::class;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        $allOperators     = parent::getOperators();
        $allowedOperators = array_flip(['empty', '!empty', 'in', '!in']);

        return array_intersect_key($allOperators, $allowedOperators);
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaultValueTransformer(): DataTransformerInterface
    {
        return new MultivalueTransformer();
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue(CustomField $customField, $value): void
    {
        parent::validateValue($customField, $value);

        if (empty($value)) {
            return;
        }

        if (is_string($value) && $this->isJson($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $options        = $customField->getOptions();
        $possibleValues = $options->map(function (CustomFieldOption $option) {
            return $option->getValue();
        })->getValues();

        foreach ($value as $singleValue) {
            if (!in_array($singleValue, $possibleValues)) {
                throw new \UnexpectedValueException($this->translator->trans('custom.field.option.invalid', ['%value%'          => $singleValue, '%fieldLabel%'     => $customField->getLabel(), '%fieldAlias%'     => $customField->getAlias(), '%possibleValues%' => $this->csvHelper->arrayToCsvLine($possibleValues)], 'validators'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createApiValueTransformer(): DataTransformerInterface
    {
        return new CsvTransformer();
    }

    /**
     * {@inheritdoc}
     */
    public function valueToString(CustomFieldValueInterface $fieldValue): string
    {
        $transformer = $this->createApiValueTransformer();
        $values      = $fieldValue->getValue();
        $labels      = [];

        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            try {
                $labels[] = $fieldValue->getCustomField()->valueToLabel((string) $value);
            } catch (NotFoundException $e) {
                // When the value does not exist anymore, use the value instead.
                $labels[] = $value;
            }
        }

        return $transformer->transform($labels);
    }

    private function isJson(string $string): bool
    {
        json_decode($string);

        return JSON_ERROR_NONE === json_last_error();
    }
}
