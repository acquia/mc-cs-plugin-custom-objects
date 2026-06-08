<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\AbstractMultivalueType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\StaticChoiceTypeInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Exception\UndefinedTransformerException;
use MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints\AllowUniqueIdentifier;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={"security"="'custom_objects:custom_fields:viewother'"},
 *          "post"={"security"="'custom_objects:custom_fields:create'"}
 *     },
 *     itemOperations={
 *          "get"={"security"="'custom_objects:custom_fields:view'"},
 *          "put"={"security"="'custom_objects:custom_fields:edit'"},
 *          "patch"={"security"="'custom_objects:custom_fields:edit'"},
 *          "delete"={"security"="'custom_objects:custom_fields:delete'"}
 *     },
 *     shortName="custom_fields",
 *     normalizationContext={"groups"={"custom_field:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"custom_field:write"}, "swagger_definition_name"="Write"}
 * )
 * @ApiFilter(SearchFilter::class, properties={"alias": "partial"})
 */
class CustomField extends FormEntity implements UniqueEntityInterface, UuidInterface
{
    use UuidTrait;

    public const TABLE_NAME  = 'custom_field';
    public const TABLE_ALIAS = 'CustomField';

    /**
     * @var int|null
     * @Groups({"custom_field:read", "custom_object:read"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="int",
     *             "nullable"=false,
     *             "example"="42"
     *         }
     *     }
     * )
     */
    private $id;

    /**
     * @var string|null
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "maxLength"=191,
     *             "nullable"=false,
     *             "example"="City"
     *         }
     *     }
     * )
     */
    private $label;

    /**
     * @var string|null
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "maxLength"=191,
     *             "nullable"=false,
     *             "example"="city"
     *         }
     *     }
     * )
     */
    private $alias;

    /**
     * @var string|null
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "maxLength"=191,
     *             "nullable"=false,
     *             "example"="text",
     *             "enum"={
     *                 "checkbox_group",
     *                 "country",
     *                 "datetime",
     *                 "date",
     *                 "email",
     *                 "hidden",
     *                 "int",
     *                 "multiselect",
     *                 "phone",
     *                 "radio_group",
     *                 "select"
     *             }
     *         }
     *     }
     * )
     */
    private $type;

    /**
     * @var CustomFieldTypeInterface|null
     */
    private $typeObject;

    /**
     * @ManyToOne(targetEntity="CustomObject", inversedBy="customFields")
     * @JoinColumn(name="custom_object_id", referencedColumnName="id")
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     *
     * @var CustomObject|null
     */
    private $customObject;

    /**
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="integer",
     *             "nullable"=true,
     *             "example"=42
     *         }
     *     }
     * )
     *
     * @var int|null
     */
    private $order;

    /**
     * @var bool
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     */
    private $required = false;

    /**
     * @var mixed
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     */
    private $defaultValue;

    /**
     * @var Collection|CustomFieldOption[]
     * @OneToMany(targetEntity="CustomFieldOption", mappedBy="customField")
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     * @ApiSubresource()
     */
    private $options;

    /**
     * @var Params|string[]
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     */
    private $params;

    /**
     * @var bool
     */
    private $showInCustomObjectDetailList = true;

    /**
     * @var bool
     */
    private $showInContactDetailList = true;

    /**
     * @Groups({"custom_field:read", "custom_field:write", "custom_object:read", "custom_object:write"})
     */
    private bool $isUniqueIdentifier = false;

    private bool $wasChangeIsUniqueIdentifier = false;

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id    = null;
        $this->alias = null;
    }

    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'label'        => $this->label,
            'type'         => $this->type,
            'customObject' => $this->customObject->getId(),
            'order'        => $this->order,
        ];
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(CustomFieldRepository::class)
            ->addIndex(['alias'], 'alias');

        $builder->createManyToOne('customObject', CustomObject::class)
            ->addJoinColumn('custom_object_id', 'id', false, false, 'CASCADE')
            ->inversedBy('customFields')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->addId();
        $builder->addField('label', Type::STRING);
        $builder->addField('alias', Type::STRING);
        $builder->addField('type', Type::STRING);
        $builder->createField('order', 'integer')
            ->columnName('field_order')
            ->nullable()
            ->build();

        $builder->createField('required', Type::BOOLEAN)
            ->columnName('required')
            ->option('default', false)
            ->build();

        $builder->createField('defaultValue', Type::STRING)
            ->columnName('default_value')
            ->nullable()
            ->build();

        $builder->createOneToMany('options', CustomFieldOption::class)
            ->setOrderBy(['order' => 'ASC'])
            ->mappedBy('customField')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('params', Type::JSON_ARRAY)
            ->columnName('params')
            ->nullable()
            ->build();

        $builder->createField('showInCustomObjectDetailList', Type::BOOLEAN)
            ->columnName('show_in_custom_object_detail_list')
            ->option('default', true)
            ->build();

        $builder->createField('showInContactDetailList', Type::BOOLEAN)
            ->columnName('show_in_contact_detail_list')
            ->option('default', true)
            ->build();

        $builder->createField('isUniqueIdentifier', Types::BOOLEAN)
            ->columnName('is_unique_identifier')
            ->option('default', false)
            ->build();

        static::addUuidField($builder);
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('label', new Assert\NotBlank());
        $metadata->addPropertyConstraint('label', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('alias', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('type', new Assert\NotBlank());
        $metadata->addPropertyConstraint('type', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('customObject', new Assert\NotBlank());
        $metadata->addPropertyConstraint('defaultValue', new Assert\Length(['max' => 255]));
        $metadata->addConstraint(new Assert\Callback('validateDefaultValue'));
        $metadata->addConstraint(new AllowUniqueIdentifier());
    }

    /**
     * Allow different field types to validate the value.
     */
    public function validateDefaultValue(ExecutionContextInterface $context): void
    {
        try {
            $this->getTypeObject()->validateValue($this, $this->defaultValue);
        } catch (\UnexpectedValueException $e) {
            $context->buildViolation($e->getMessage())
                // ->atPath('defaultValue') // Somehow doesn't validate when we set the path...
                ->addViolation();
        }
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null Null when it is filled as new entity with PropertyAccessor
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLabel(?string $label): void
    {
        $this->isChanged('label', $label);
        $this->label = $label;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Alias for abstractions. Do not use.
     */
    public function getName(): ?string
    {
        return $this->getLabel();
    }

    /**
     * @param string|null $alias
     */
    public function setAlias($alias)
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setTypeObject(CustomFieldTypeInterface $typeObject): void
    {
        $this->typeObject = $typeObject;
    }

    public function getTypeObject(): ?CustomFieldTypeInterface
    {
        return $this->typeObject;
    }

    /**
     * @param mixed[] $customOptions
     *
     * @return mixed[]
     */
    public function getFormFieldOptions(array $customOptions = []): array
    {
        $fieldTypeOptions = $this->getTypeObject()->createFormTypeOptions();
        $choices          = $this->getChoices();
        $placeholder      = $this->getPlaceholder();

        $fieldOptions     = [
            'label'      => $this->getLabel(),
            'required'   => $this->isRequired(),
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ];

        if ($placeholder) {
            $fieldOptions['attr']['data-placeholder'] = $placeholder;
        }

        if ($choices) {
            $fieldOptions['choices'] = $choices;
        }

        return array_replace_recursive($fieldTypeOptions, $fieldOptions, $customOptions);
    }

    public function getCustomObject(): ?CustomObject
    {
        return $this->customObject;
    }

    /**
     * @param CustomObject $customObject
     */
    public function setCustomObject(?CustomObject $customObject = null): void
    {
        $this->customObject = $customObject;
        if ($customObject) {
            $this->isChanged('customObject', $customObject->getId());
        }
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): void
    {
        $this->order = $order;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired(?bool $required): void
    {
        $this->required = $this->isUniqueIdentifier ?: (bool) $required;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        try {
            return $this->getTypeObject()->createDefaultValueTransformer()->transform($this->defaultValue);
        } catch (UndefinedTransformerException $e) {
            // Nothing to transform, return string below
        }

        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): void
    {
        try {
            $this->defaultValue = $this->getTypeObject()->createDefaultValueTransformer()->reverseTransform($defaultValue);

            return;
        } catch (UndefinedTransformerException $e) {
            // Nothing to transform, use string below
        }

        $this->defaultValue = '' === $defaultValue ? null : $defaultValue;
    }

    /**
     * @param CustomFieldOption|string[] $option
     */
    public function addOption($option): void
    {
        if (is_array($option)) {
            $option = new CustomFieldOption($option);
        }

        $option->setOrder($this->options->count());
        $this->options->add($option);
        $option->setCustomField($this);
    }

    /**
     * @param Collection|CustomFieldOption[] $options
     */
    public function setOptions(Collection $options): void
    {
        $order = 0;

        foreach ($options as $option) {
            $option->setCustomField($this);
            $option->setOrder($order);
            ++$order;
        }

        $this->options = $options;
    }

    public function removeOption(CustomFieldOption $option): void
    {
        $this->options->removeElement($option);
    }

    /**
     * @return Collection|CustomFieldOption[]
     */
    public function getOptions(): Collection
    {
        if ($this->isChoiceType()) {
            return $this->options;
        }

        return new ArrayCollection();
    }

    /**
     * Makes an array of choices from options for Symfony form.
     *
     * @return mixed[]
     */
    public function getChoices(): array
    {
        $choices = [];

        if ($this->getTypeObject() instanceof StaticChoiceTypeInterface) {
            $choices = $this->getTypeObject()->getChoices();
        } else {
            foreach ($this->getOptions() as $option) {
                $choices[$option->getLabel()] = $option->getValue();
            }
        }

        return $choices;
    }

    /**
     * Method for multi/select fields that will convert a value to its label.
     *
     * @throws NotFoundException
     */
    public function valueToLabel(string $value): string
    {
        $choices = $this->getChoices();
        $label   = array_search($value, $choices, true);

        if (false === $label) {
            throw new NotFoundException("Label was not found for value {$value}");
        }

        return (string) $label;
    }

    /**
     * @return Params|string[]
     */
    public function getParams()
    {
        if ($this->params) {
            if (is_array($this->params)) {
                // @todo this should not happen, but when fetching CO, lazy loaded CF is not using CustomFieldPostLoadSubscriber
                $this->params = new Params($this->params);
            }

            return $this->params;
        }

        return new Params();
    }

    /**
     * @param Params|string[] $params
     */
    public function setParams($params): void
    {
        $this->params = $params;
    }

    public function isShowInCustomObjectDetailList(): bool
    {
        return $this->showInCustomObjectDetailList;
    }

    public function setShowInCustomObjectDetailList(?bool $showInCustomObjectDetailList): void
    {
        $this->showInCustomObjectDetailList = (bool) $showInCustomObjectDetailList;
    }

    public function isShowInContactDetailList(): bool
    {
        return $this->showInContactDetailList;
    }

    public function setShowInContactDetailList(?bool $showInContactDetailList): void
    {
        $this->showInContactDetailList = (bool) $showInContactDetailList;
    }

    public function isChoiceType(): bool
    {
        return ChoiceType::class === $this->getTypeObject()->getSymfonyFormFieldType() ||
            is_subclass_of($this->getTypeObject()->getSymfonyFormFieldType(), ChoiceType::class);
    }

    public function canHaveMultipleValues(): bool
    {
        return $this->getTypeObject() instanceof AbstractMultivalueType;
    }

    /**
     * @return string|null
     */
    private function getPlaceholder()
    {
        $params      = $this->getParams();
        $placeholder = null;

        if (is_object($params)) {
            $placeholder = $params->getPlaceholder();
        } elseif (array_key_exists('placeholder', $params)) {
            $placeholder = $params['placeholder'];
        }

        return $placeholder;
    }

    /**
     * @Groups({"custom_field:read", "custom_object:read"})
     *
     * @return bool
     */
    public function getIsPublished()
    {
        return parent::getIsPublished();
    }

    /**
     * @Groups({"custom_field:read", "custom_object:read"})
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return parent::getDateAdded();
    }

    /**
     * @Groups({"custom_field:read", "custom_object:read"})
     *
     * @return \DateTime
     */
    public function getDateModified()
    {
        return parent::getDateModified();
    }

    /**
     * @Groups({"custom_field:write", "custom_object:write"})
     *
     * @param bool $isPublished
     *
     * @return $this
     */
    public function setIsPublished($isPublished)
    {
        parent::setIsPublished($isPublished);

        return $this;
    }

    public function wasChangeIsUniqueIdentifier(): bool
    {
        return $this->wasChangeIsUniqueIdentifier;
    }

    public function getIsUniqueIdentifier(): bool
    {
        return $this->isUniqueIdentifier;
    }

    public function setIsUniqueIdentifier(?bool $isUniqueIdentifier): void
    {
        $this->wasChangeIsUniqueIdentifier = $this->isUniqueIdentifier != $isUniqueIdentifier;
        $this->isUniqueIdentifier          = (bool) $isUniqueIdentifier;
        if ($isUniqueIdentifier) {
            $this->setRequired($isUniqueIdentifier);
        }
    }
}
