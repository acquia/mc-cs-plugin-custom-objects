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
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\FormEntity;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Mautic\CoreBundle\Helper\ArrayHelper;

class CustomItem extends FormEntity implements UniqueEntityInterface
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var CustomObject
     */
    private $customObject;

    /**
     * @var string|null
     */
    private $language;

    /**
     * @var Category|null
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $customFieldValues;

    /**
     * @var array
     */
    private $initialCustomFieldValues = [];

    /**
     * @var ArrayCollection
     */
    private $contactReferences;

    /**
     * @var ArrayCollection
     */
    private $companyReferences;

    /**
     * @param CustomObject $customObject
     */
    public function __construct(CustomObject $customObject)
    {
        $this->customObject      = $customObject;
        $this->customFieldValues = new ArrayCollection();
        $this->contactReferences = new ArrayCollection();
        $this->companyReferences = new ArrayCollection();
    }

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

        $builder->setTable('custom_item')
            ->setCustomRepositoryClass(CustomItemRepository::class);

        $builder->createManyToOne('customObject', CustomObject::class)
            ->addJoinColumn('custom_object_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('contactReferences', CustomItemXrefContact::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->mappedBy('customItem')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('companyReferences', CustomItemXrefCompany::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->mappedBy('customItem')
            ->fetchExtraLazy()
            ->build();

        $builder->addBigIntIdField();
        $builder->addCategory();
        $builder->addField('name', Type::STRING);
        $builder->addNullableField('language', Type::STRING, 'lang');
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
        $metadata->addPropertyConstraint('name', new Assert\Length(['max' => 255]));
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return (int) $this->id;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->isChanged('name', $name);
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return CustomObject
     */
    public function getCustomObject(): CustomObject
    {
        return $this->customObject;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     */
    public function setCategory(?Category $category): void
    {
        $this->isChanged('category', $category ? $category->getId() : null);
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string|null $language
     */
    public function setLanguage(?string $language): void
    {
        $this->isChanged('language', $language);
        $this->language = $language;
    }

    /**
     * @param CustomFieldValueInterface $customFieldValue
     */
    public function addCustomFieldValue(CustomFieldValueInterface $customFieldValue): void
    {
        if ($this->customFieldValues === null) {
            $this->customFieldValues = new ArrayCollection();
        }

        $this->customFieldValues->add($customFieldValue);
    }

    /**
     * @param ArrayCollection $customFieldValues
     */
    public function setCustomFieldValues(ArrayCollection $customFieldValues): void
    {
        $this->customFieldValues = $customFieldValues;
    }

    /**
     * Called when the custom field values are loaded from the database.
     */
    public function createFieldValuesSnapshot(): void
    {
        foreach ($this->customFieldValues as $customFieldValue) {
            $this->initialCustomFieldValues[$customFieldValue->getCustomField()->getId()] = $customFieldValue->getValue();
        }
    }

    /**
     * Called before CustomItemSave. It will record changes that happened for custom field values.
     */
    public function recordCustomFieldValueChanges(): void
    {
        foreach ($this->customFieldValues as $customFieldValue) {
            $customFieldId = $customFieldValue->getCustomField()->getId();
            $initialValue  = ArrayHelper::getValue($customFieldId, $this->initialCustomFieldValues);
            $newValue      = $customFieldValue->getValue();

            if ($initialValue !== $newValue) {
                $this->addChange("customfieldvalue:{$customFieldId}", [$initialValue, $newValue]);
            }
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getCustomFieldValues(): ArrayCollection
    {
        if ($this->customFieldValues === null) {
            $this->customFieldValues = new ArrayCollection();
        }

        return $this->customFieldValues;
    }

    /**
     * @param int $customFieldId
     *
     * @return CustomFieldValueInterface
     *
     * @throws NotFoundException
     */
    public function findCustomFieldValueForFieldId(int $customFieldId): CustomFieldValueInterface
    {
        $customFieldValue = $this->customFieldValues->get($customFieldId);

        if (!$customFieldValue) {
            throw new NotFoundException("Custom Field Value for ID = {$customFieldId} was not found.");
        }

        return $customFieldValue;
    }

    /**
     * @param CustomItemXrefContact $reference
     */
    public function addContactReference(CustomItemXrefContact $reference): void
    {
        $this->contactReferences->add($reference);
    }

    /**
     * @return Collection
     */
    public function getContactReferences(): Collection
    {
        return $this->contactReferences;
    }

    /**
     * @param CustomItemXrefCompany $reference
     */
    public function addCompanyReference(CustomItemXrefCompany $reference): void
    {
        $this->companyReferences->add($reference);
    }

    /**
     * @return Collection
     */
    public function getCompanyReferences(): Collection
    {
        return $this->companyReferences;
    }
}
