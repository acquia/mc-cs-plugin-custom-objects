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

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Table for multiselect/checkbox option values.
 */
class CustomFieldValueOption extends AbstractCustomFieldValue
{
    /**
     * @var string[]|string|null
     */
    private $value;

    /**
     * @param CustomField          $customField
     * @param CustomItem           $customItem
     * @param string|string[]|null $value
     */
    public function __construct(CustomField $customField, CustomItem $customItem, $value = null)
    {
        parent::__construct($customField, $customItem);

        $this->setValue($value);
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('custom_field_value_option');

        parent::addReferenceColumns($builder);

        $builder->createField('value', Type::STRING)
            ->makePrimaryKey()
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Assert\Callback('validateOptionValueExists'));
    }

    /**
     * Validate whether the value exists also as the option value.
     *
     * @param ExecutionContextInterface $context
     */
    public function validateOptionValueExists(ExecutionContextInterface $context): void
    {
        $customField = $this->getCustomField();
        $valueExists = $customField->getOptions()->exists(function (int $key, CustomFieldOption $option) {
            return $this->getValue() === $option->getValue();
        });

        if (!$valueExists) {
            $possibleValues = implode(', ', $customField->getOptions()->map(function (CustomFieldOption $option) {
                return $option->getValue();
            })->getValues());
            $context->buildViolation("Value '{$this->getValue()}' does not exist in the list of options of field '{$customField->getLabel()}' ({$customField->getId()}). Possible values: {$possibleValues}")
                ->atPath('value')
                ->addViolation();
        }
    }

    /**
     * @param mixed $value
     */
    public function addValue($value = null)
    {
        if (!$this->value) {
            $this->value = [];
        }

        $this->value[] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value = null)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
