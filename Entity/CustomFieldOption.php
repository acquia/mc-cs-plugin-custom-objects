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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CustomFieldOption implements \ArrayAccess
{
    /**
     * @var CustomField|null
     */
    private $customField;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $value;

    /**
     * @var int|null
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
