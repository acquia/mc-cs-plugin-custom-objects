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
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedConstraintsException;

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
     * @param TranslatorInterface $translator
     *
     * @return string[]
     */
    public function getOperatorOptions(TranslatorInterface $translator): array
    {
        $operators = $this->getOperators();
        $options   = [];

        foreach ($operators as $key => $operator) {
            $options[$key] = $translator->trans($operator['label']);
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
    public function validateValue($value = null, ExecutionContextInterface $context): void
    {
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
     * {@inheritdoc}
     */
    public function getSymfonyFormConstraints(): array
    {
        throw new UndefinedConstraintsException();
    }
}
