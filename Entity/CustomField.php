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
     * @var string
     */
    private $id;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $alias;

    /**
     * @var DateTimeInterface|null
     */
    private $dateAdded;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var CustomObject|null
     */
    private $customObject;

    public function __clone()
    {
        $this->id    = null;
        $this->alias = null;
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('custom_field')
            ->setCustomRepositoryClass(CustomFieldRepository::class)
            ->addIndex(['alias'], 'alias');

        $builder->createManyToOne('customObject', CustomObject::class)
            // ->inversedBy('customField')
            ->addJoinColumn('custom_object_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addUuid();
        $builder->addField('label', Type::STRING);
        $builder->addField('alias', Type::STRING);
        $builder->addField('type', Type::STRING);
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('label', new Assert\NotBlank());
        $metadata->addPropertyConstraint('type', new Assert\NotBlank());
        $metadata->addPropertyConstraint('customObject', new Assert\NotBlank());
        $metadata->addPropertyConstraint('label', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('alias', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('type', new Assert\Length(['max' => 255]));
    }

    /**
     * @return string
     */
    public function getId(): string
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
     * Alias for astractions. Do not use.
     * 
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getLabel();
    }

    /**
     * @param string|null $alias
     */
    public function setAlias(?string $alias): void
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param CustomFieldTypeInterface|null $type
     */
    public function setType(?CustomFieldTypeInterface $type): void
    {
        $this->isChanged('type', $type->getKey());
        $this->type = $type->getKey();
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return CustomObject
     */
    public function getCustomObject(): ?CustomObject
    {
        return $this->customObject;
    }

    /**
     * @param CustomObject|null $customObject
     */
    public function setCustomObject(?CustomObject $customObject): void
    {
        $this->isChanged('customObject', $customObject->getId());
        $this->customObject = $customObject;
    }
}
