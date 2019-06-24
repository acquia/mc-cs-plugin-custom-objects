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

use MauticPlugin\CustomObjectsBundle\CustomFieldType\DataTransformer\MultivalueTransformer;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldOption;

abstract class AbstractMultivalueType extends AbstractCustomFieldType
{
    public const TABLE_NAME = 'custom_field_value_option';

    /**
     * @var CsvHelper
     */
    private $csvHelper;

    /**
     * @param TranslatorInterface $translator
     * @param CsvHelper           $csvHelper
     */
    public function __construct(TranslatorInterface $translator, CsvHelper $csvHelper)
    {
        parent::__construct($translator);

        $this->csvHelper = $csvHelper;
    }

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     * @param mixed|null  $value
     *
     * @return CustomFieldValueInterface
     */
    public function createValueEntity(CustomField $customField, CustomItem $customItem, $value = null): CustomFieldValueInterface
    {
        return new CustomFieldValueOption($customField, $customItem, $value);
    }

    /**
     * @return string
     */
    public function getSymfonyFormFieldType(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return string
     */
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

        if (is_string($value)) {
            if ($this->isJson($value)) {
                $value = json_decode($value, true);
            } else {
                $value = [$value];
            }
        }

        $options        = $customField->getOptions();
        $possibleValues = $options->map(function (CustomFieldOption $option) {
            return $option->getValue();
        })->getValues();

        foreach ($value as $singleValue) {
            if (!in_array($singleValue, $possibleValues)) {
                throw new \UnexpectedValueException(
                    $this->translator->trans(
                        'custom.field.option.invalid',
                        [
                            '%value%'          => $singleValue,
                            '%fieldLabel%'     => $customField->getLabel(),
                            '%fieldAlias%'     => $customField->getAlias(),
                            '%possibleValues%' => $this->csvHelper->arrayToCsvLine($possibleValues),
                        ],
                        'validators'
                    )
                );
            }
        }
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function isJson(string $string): bool
    {
        json_decode($string);

        return JSON_ERROR_NONE === json_last_error();
    }
}
