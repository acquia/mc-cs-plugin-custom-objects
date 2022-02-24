<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={"security"="'custom_objects:custom_fields:viewother'"},
 *          "post"={"security"="'custom_objects:custom_fields:create'"}
 *     },
 *     itemOperations={
 *          "get"={"security"="'custom_objects:custom_fields:view(getCustomField)'"},
 *          "put"={"security"="'custom_objects:custom_fields:edit(getCustomField)'"},
 *          "patch"={"security"="'custom_objects:custom_fields:edit(getCustomField)'"},
 *          "delete"={"security"="'custom_objects:custom_fields:delete(getCustomField)'"}
 *     },
 *     shortName="custom_field_options"
 * )
 */
class CustomFieldOption implements \ArrayAccess
{
    /**
     * @var CustomField|null
     * @Id @Column(type="integer")
     * @ManyToOne(targetEntity="CustomField", inversedBy="options")
     * @JoinColumn("custom_field_id")
     */
    private $customField;

    /**
     * @var string|null
     * @Groups({"custom_object:read", "custom_object:write", "custom_field:read", "custom_field:write"})
     */
    private $label;

    /**
     * @var string|null
     * @Id @Column(type="integer")
     * @Groups({"custom_object:read", "custom_object:write", "custom_field:read", "custom_field:write"})
     */
    private $value;

    /**
     * @var int|null
     * @Groups({"custom_object:read", "custom_field:read"})
     */
    private $order;

    /**
     * @param mixed[] $option
     */
    public function __construct(array $option = [])
    {
        foreach ($option as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return mixed[]
     */
    public function __toArray(): array
    {
        $return = [
            'customField' => $this->customField ? $this->customField->getId() : null,
            'label'       => $this->label,
            'value'       => $this->value,
            'order'       => $this->order,
        ];

        return array_filter($return);
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('custom_field_option');

        $builder->createManyToOne('customField', CustomField::class)
            ->addJoinColumn('custom_field_id', 'id', false, false, 'CASCADE')
            ->inversedBy('options')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->makePrimaryKey()
            ->build();

        $builder->createField('value', Type::STRING)
            ->makePrimaryKey()
            ->build();

        $builder->addField('label', Type::STRING);

        $builder->createField('order', 'integer')
            ->columnName('option_order')
            ->option('unsigned', true)
            ->nullable()
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('label', new Assert\NotBlank());
        $metadata->addPropertyConstraint('label', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('value', new Assert\NotNull());
        $metadata->addPropertyConstraint('value', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('order', new Assert\NotNull());
    }

    public function getCustomField(): ?CustomField
    {
        return $this->customField;
    }

    public function setCustomField(CustomField $customField): void
    {
        $this->customField = $customField;
    }

    public function getLabel(): string
    {
        return (string) $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getValue(): string
    {
        return (string) $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): void
    {
        $this->order = $order;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->{$offset} : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }
}
