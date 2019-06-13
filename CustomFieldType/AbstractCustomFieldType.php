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

use Mautic\LeadBundle\Segment\OperatorOptions;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;

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
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->translator->trans(static::NAME);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getTableAlias(): string
    {
        return 'cfv_'.$this->getKey();
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        return OperatorOptions::getFilterExpressionFunctions();
    }

    /**
     * @return string
     */
    public function getTableName(): string
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
    public function validateValue(CustomFieldValueInterface $valueEntity, ExecutionContextInterface $context): void
    {
        $this->validateEmptyIfRequired($valueEntity, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function useEmptyValue(): bool
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
     * @param CustomFieldValueInterface $valueEntity
     * @param ExecutionContextInterface $context
     */
    protected function validateEmptyIfRequired(CustomFieldValueInterface $valueEntity, ExecutionContextInterface $context): void
    {
        if (!$valueEntity->getCustomField()->isRequired()) {
            return;
        }

        $value        = $valueEntity->getValue();
        $valueIsEmpty = false === $value || (empty($value) && '0' !== $value && 0 !== $value);

        if (!$valueIsEmpty) {
            return;
        }

        $context->buildViolation(
            $this->translator->trans(
                    'custom.field.required',
                    ['%fieldName%' => "{$valueEntity->getCustomField()->getLabel()} ({$valueEntity->getCustomField()->getAlias()})"],
                    'validators'
                )
            )
            ->atPath('value')
            ->addViolation();
    }
}
