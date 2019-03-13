<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField\Params;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Mautic\CoreBundle\Entity\FormEntity;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;

class CustomField extends FormEntity implements UniqueEntityInterface
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var CustomFieldTypeInterface|null
     */
    private $typeObject;

    /**
     * @var CustomObject|null
     */
    private $customObject;

    /**
     * @var int|null
     */
    private $order;

    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var Params|string[]
     */
    private $params;

    public function __clone()
    {
        $this->id = null;
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

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('custom_field')
            ->setCustomRepositoryClass(CustomFieldRepository::class);

        $builder->createManyToOne('customObject', CustomObject::class)
            ->addJoinColumn('custom_object_id', 'id', false, false, 'CASCADE')
            ->inversedBy('customFields')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->addId();
        $builder->addField('label', Type::STRING);
        $builder->addField('type', Type::STRING);
        $builder->createField('order', 'integer')
            ->columnName('field_order')
            ->nullable()
            ->build();
        $builder->createField('required', Type::BOOLEAN)
            ->columnName('required')
            ->nullable()
            ->build();
        $builder->createField('defaultValue', Type::STRING)
            ->columnName('default_value')
            ->nullable()
            ->build();
        $builder->createField('params', Type::JSON_ARRAY)
            ->columnName('params')
            ->nullable()
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('label', new Assert\NotBlank());
        $metadata->addPropertyConstraint('label', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('type', new Assert\NotBlank());
        $metadata->addPropertyConstraint('type', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('customObject', new Assert\NotBlank());
//        $metadata->addPropertyConstraint('defaultValue', new Assert\Length(['max' => 255]));
    }

    /**
     * @param int|null $id
     */
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

    /**
     * @param string|null $label
     */
    public function setLabel(?string $label): void
    {
        $this->isChanged('label', $label);
        $this->label = $label;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Alias for abstractions. Do not use.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getLabel();
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param CustomFieldTypeInterface $typeObject
     */
    public function setTypeObject(CustomFieldTypeInterface $typeObject): void
    {
        $this->typeObject = $typeObject;
    }

    /**
     * @return CustomFieldTypeInterface|null
     */
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
        $fieldOptions     = [
            'label'      => $this->getLabel(),
            'required'   => $this->isRequired(),
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ];

        return array_merge_recursive($fieldTypeOptions, $fieldOptions, $customOptions);
    }

    /**
     * @return CustomObject|null
     */
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

    /**
     * @return int|null
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * @param int|null $order
     */
    public function setOrder(?int $order): void
    {
        $this->order = $order;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired(?bool $required): void
    {
        $this->required = (bool) $required;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return Params|string[]
     */
    public function getParams()
    {
        if ($this->params) {
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
}
