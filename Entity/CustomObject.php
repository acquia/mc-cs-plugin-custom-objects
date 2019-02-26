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
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CoreBundle\Helper\ArrayHelper;

class CustomObject extends FormEntity implements UniqueEntityInterface
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $namePlural;

    /**
     * @var string|null
     */
    private $nameSingular;

    /**
     * @var string|null
     */
    private $description;

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
    private $customFields;

    /**
     * @var mixed[]
     */
    private $initialCustomFields = [];

    public function __construct()
    {
        $this->customFields = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id  = null;
        $this->new = true;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('custom_object')
            ->setCustomRepositoryClass(CustomObjectRepository::class);

        $builder->createOneToMany('customFields', CustomField::class)
            ->setOrderBy(['order' => 'ASC'])
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->mappedBy('customObject')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->addId();
        $builder->addCategory();
        $builder->addNamedField('namePlural', Type::STRING, 'name_plural');
        $builder->addNamedField('nameSingular', Type::STRING, 'name_singular');
        $builder->addNullableField('language', Type::STRING, 'lang');
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('namePlural', new Assert\NotBlank());
        $metadata->addPropertyConstraint('namePlural', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('nameSingular', new Assert\NotBlank());
        $metadata->addPropertyConstraint('nameSingular', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('description', new Assert\Length(['max' => 65535]));
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * This is alias method that is required by Mautic.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getNamePlural();
    }

    /**
     * @param string|null $namePlural
     */
    public function setNamePlural(?string $namePlural): void
    {
        $this->isChanged('namePlural', $namePlural);
        $this->namePlural = $namePlural;
    }

    /**
     * @return string|null
     */
    public function getNamePlural()
    {
        return $this->namePlural;
    }

    /**
     * @param string|null $nameSingular
     */
    public function setNameSingular(?string $nameSingular): void
    {
        $this->isChanged('nameSingular', $nameSingular);
        $this->nameSingular = $nameSingular;
    }

    /**
     * @return string|null
     */
    public function getNameSingular()
    {
        return $this->nameSingular;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->isChanged('description', $description);
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     */
    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getLanguage()
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
     * @param \MauticPlugin\CustomObjectsBundle\Entity\CustomField $customField
     */
    public function addCustomField(CustomField $customField): void
    {
        $customField->setCustomObject($this);
        $this->customFields->add($customField);
    }

    /**
     * @param \MauticPlugin\CustomObjectsBundle\Entity\CustomField $customField
     */
    public function removeCustomField(CustomField $customField): void
    {
        $this->customFields->removeElement($customField);
        $customField->setCustomObject();
    }

    /**
     * @return Collection
     */
    public function getCustomFields()
    {
        return $this->customFields;
    }

    /**
     * Called when the custom fields are loaded from the database.
     */
    public function createFieldsSnapshot(): void
    {
        foreach ($this->customFields as $customField) {
            $this->initialCustomFields[$customField->getId()] = $customField->toArray();
        }
    }

    /**
     * Called before CustomObjectSave. It will record changes that happened for custom fields.
     */
    public function recordCustomFieldChanges(): void
    {
        $existingFields = [];
        foreach ($this->customFields as $i => $customField) {
            $initialField = ArrayHelper::getValue($customField->getId(), $this->initialCustomFields, []);
            $newField     = $customField->toArray();

            // In case the user added more than 1 new field, add it unique ID.
            // Custom Field ID for new fields is not known yet in this point.
            if (empty($newField['id'])) {
                $newField['id'] = "temp_{$i}";
            } else {
                $existingFields[$newField['id']] = $newField;
            }

            foreach ($newField as $key => $newValue) {
                $initialValue = ArrayHelper::getValue($key, $initialField);
                if ($initialValue !== $newValue) {
                    $this->addChange("customfield:{$newField['id']}:{$key}", [$initialValue, $newValue]);
                }
            }
        }

        $deletedFields = array_diff_key($this->initialCustomFields, $existingFields);

        foreach ($deletedFields as $deletedField) {
            $this->addChange("customfield:{$deletedField['id']}", [null, 'deleted']);
        }
    }
}
