<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

abstract class AbstractCustomFieldValue implements CustomFieldValueInterface
{
    /**
     * @var CustomField
     */
    protected $customField;

    /**
     * @var CustomItem
     */
    protected $customItem;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setMappedSuperClass();
    }

    public function __construct(CustomField $customField, CustomItem $customItem)
    {
        $this->customField = $customField;
        $this->customItem  = $customItem;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('customField', new Assert\NotBlank());
        $metadata->addPropertyConstraint('customItem', new Assert\NotBlank());
        $metadata->addConstraint(new Assert\Callback('validateValue'));
    }

    /**
     * Allow different field types to validate the value.
     */
    public function validateValue(ExecutionContextInterface $context): void
    {
        try {
            $fieldType = $this->getCustomField()->getTypeObject();
            $fieldType->validateValue($this->getCustomField(), $this->getValue());
            $fieldType->validateRequired($this->getCustomField(), $this->getValue());
        } catch (\UnexpectedValueException $e) {
            $context->buildViolation($e->getMessage())
                ->atPath('value')
                ->addViolation();
        }
    }

    public function getId(): int
    {
        return $this->customField->getId();
    }

    public function getCustomField(): CustomField
    {
        return $this->customField;
    }

    public function setCustomItem(CustomItem $customItem): void
    {
        $this->customItem = $customItem;
    }

    public function getCustomItem(): CustomItem
    {
        return $this->customItem;
    }

    /**
     * @param mixed $value
     */
    public function addValue($value = null)
    {
        throw new \Exception('addValue is not implemented for '.self::class);
    }

    protected static function addReferenceColumns(ClassMetadataBuilder $builder): void
    {
        $builder->createManyToOne('customField', CustomField::class)
            ->addJoinColumn('custom_field_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('customItem', CustomItem::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();
    }
}
