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
use MauticPlugin\CustomObjectsBundle\CustomFieldType\IntType;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeInterface;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\FormEntity;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Entity\UniqueEntityInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;
use Ramsey\Uuid\Uuid;

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
     * @var DateTimeInterface|null
     */
    private $dateAdded;

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

    public function __clone()
    {
        $this->id = null;
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
            ->inversedBy('fields')
            ->fetchExtraLazy()
            ->build();

        $builder->addId();
        $builder->addField('label', Type::STRING);
        $builder->addField('type', Type::STRING);
        $builder->addField('order', Type::INTEGER);
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
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string|null $label
     */
    public function setLabel($label)
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
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param CustomFieldTypeInterface|string $type
     */
    public function setTypeObject(CustomFieldTypeInterface $typeObject): void
    {
        $this->typeObject = $typeObject;
    }

    /**
     * @return CustomFieldTypeInterface|null
     */
    public function getTypeObject()
    {
        return $this->typeObject;
    }

    /**
     * @return CustomObject|null
     */
    public function getCustomObject()
    {
        return $this->customObject;
    }

    /**
     * @param CustomObject $customObject
     */
    public function setCustomObject(CustomObject $customObject = null)
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
}
