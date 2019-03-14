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

namespace MauticPlugin\CustomObjectsBundle\Entity\CustomField;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Option
{
    /**
     * @var int|null
     */
    private $id;

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
     * @param mixed[] $option
     */
    public function __construct(array $option = [])
    {
        foreach ($option as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('custom_field_option');

        $builder->createManyToOne('customField', CustomField::class)
            ->addJoinColumn('custom_field_id', 'id', false, false, 'CASCADE')
            ->inversedBy('options')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->addId();
        $builder->addField('label', Type::STRING);
        $builder->addField('value', Type::STRING);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('label', new Assert\NotBlank());
        $metadata->addPropertyConstraint('label', new Assert\Length(['max' => 255]));
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
     * @return CustomField|null
     */
    public function getCustomField(): ?CustomField
    {
        return $this->customField;
    }

    /**
     * @param CustomField $customField
     */
    public function setCustomField(CustomField $customField): void
    {
        $this->customField = $customField;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
