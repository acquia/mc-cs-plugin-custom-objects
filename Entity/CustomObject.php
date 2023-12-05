<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\ArrayHelper;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints\CustomObjectTypeValues;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={"security"="'custom_objects:custom_objects:viewother'"},
 *          "post"={"security"="'custom_objects:custom_objects:create'"}
 *     },
 *     itemOperations={
 *          "get"={"security"="'custom_objects:custom_objects:view'"},
 *          "patch"={"security"="'custom_objects:custom_objects:edit'"},
 *          "delete"={"security"="'custom_objects:custom_objects:delete'"}
 *     },
 *     shortName="custom_objects",
 *     normalizationContext={"groups"={"custom_object:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"custom_object:write"}, "swagger_definition_name"="Write"}
 * )
 */
class CustomObject extends FormEntity implements UniqueEntityInterface
{
    public const TABLE_NAME  = 'custom_object';
    public const TABLE_ALIAS = 'CustomObject';

    // Object type constants for $type field
    public const TYPE_MASTER       = 0;
    public const TYPE_RELATIONSHIP = 1;

    /**
     * @var int|null
     * @Groups({"custom_object:read"})
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
     * @Groups({"custom_object:read", "custom_object:write"})
     */
    private $alias;

    /**
     * @var string|null
     * @Groups({"custom_object:read", "custom_object:write"})
     */
    private $nameSingular;

    /**
     * @var string|null
     * @Groups({"custom_object:read", "custom_object:write"})
     */
    private $namePlural;

    /**
     * @var string|null
     * @Groups({"custom_object:read", "custom_object:write"})
     */
    private $description;

    /**
     * @var string|null
     * @Groups({"custom_object:read", "custom_object:write"})
     */
    private $language;

    /**
     * @var Category|null
     * @Assert\Valid
     **/
    private $category;

    /**
     * @var ArrayCollection
     * @Groups({"custom_object:read", "custom_object:write"})
     */
    private $customFields;

    /**
     * @var int|null
     * @Groups({"custom_object:read", "custom_object:write"})
     */
    private $type = self::TYPE_MASTER;

    /**
     * @var CustomObject|null
     * @OneToOne(targetEntity="CustomObject")
     * @JoinColumn(name="master_object", referencedColumnName="id")
     * @Groups({"custom_object:read", "custom_object:write"})
     * @ApiProperty(readableLink=false, writableLink=false)
     */
    private $masterObject;

    /**
     * @var CustomObject|null
     * @OneToOne(targetEntity="CustomObject")
     * @JoinColumn(name="relationship_object", referencedColumnName="id", onDelete="SET NULL")
     * @Groups({"custom_object:read", "custom_object:write"})
     * @ApiProperty(readableLink=false, writableLink=false)
     */
    private $relationshipObject;

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
        $this->id    = null;
        $this->new   = true;
        $this->alias = null;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(CustomObjectRepository::class)
            ->addIndex(['alias'], 'alias');

        $builder->createOneToMany('customFields', CustomField::class)
            ->setOrderBy(['order' => 'ASC'])
            ->addJoinColumn('custom_field_id', 'id', false, false, 'CASCADE')
            ->mappedBy('customObject')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->addId();
        $builder->addCategory();
        $builder->addField('alias', Type::STRING);
        $builder->addNamedField('nameSingular', Type::STRING, 'name_singular');
        $builder->addNamedField('namePlural', Type::STRING, 'name_plural');
        $builder->addNullableField('description', Type::STRING, 'description');
        $builder->addNullableField('language', Type::STRING, 'lang');
        $builder->addNullableField('type', Type::INTEGER);

        $builder->createOneToOne('relationshipObject', CustomObject::class)
            ->addJoinColumn('relationship_object', 'id', true, false, 'SET NULL')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToOne('masterObject', CustomObject::class)
            ->addJoinColumn('master_object', 'id', true, false, 'CASCADE')
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('alias', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('nameSingular', new Assert\NotBlank());
        $metadata->addPropertyConstraint('nameSingular', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('namePlural', new Assert\NotBlank());
        $metadata->addPropertyConstraint('namePlural', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('description', new Assert\Length(['max' => 255]));
        $metadata->addConstraint(new CustomObjectTypeValues());
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getMasterObject(): ?CustomObject
    {
        return $this->masterObject;
    }

    public function setMasterObject(?CustomObject $customObject): void
    {
        $this->masterObject = $customObject;
    }

    public function getRelationshipObject(): ?CustomObject
    {
        return $this->relationshipObject;
    }

    public function setRelationshipObject(?CustomObject $customObject): void
    {
        $this->relationshipObject = $customObject;
    }

    /**
     * @param string|null $nameSingular
     */
    public function setNameSingular($nameSingular)
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
     * @param string|null $namePlural
     */
    public function setNamePlural($namePlural)
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
     * @param string|null $description
     */
    public function setDescription($description)
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
    public function setCategory($category)
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
    public function setLanguage($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;
    }

    public function addCustomField(CustomField $customField)
    {
        $customField->setCustomObject($this);
        $this->customFields->add($customField);
    }

    public function setCustomFields(ArrayCollection $customFields)
    {
        $this->customFields = $customFields;
    }

    public function removeCustomField(CustomField $customField)
    {
        $this->customFields->removeElement($customField);
        $customField->setCustomObject();
    }

    /**
     * @return Collection|CustomField[]
     */
    public function getCustomFields()
    {
        return $this->customFields;
    }

    /**
     * @param int $order
     *
     * @return CustomField
     *
     * @throws NotFoundException
     */
    public function getCustomFieldByOrder($order)
    {
        /** @var CustomField $customField */
        foreach ($this->customFields as $customField) {
            if ($customField->getOrder() === (int) $order) {
                return $customField;
            }
        }

        throw new NotFoundException("Custom field with order index '${order}' not found.");
    }

    public function getPublishedFields(): Collection
    {
        return $this->customFields->filter(
            function (CustomField $customField) {
                return $customField->isPublished();
            }
        );
    }

    public function getFieldsShowInCustomObjectDetailList(): Collection
    {
        return $this->customFields->filter(
            function (CustomField $customField) {
                return $customField->isShowInCustomObjectDetailList();
            }
        );
    }

    public function getFieldsShowInContactDetailList(): Collection
    {
        return $this->customFields->filter(
            function (CustomField $customField) {
                return $customField->isShowInContactDetailList();
            }
        );
    }

    public function getUniqueIdentifierFields(): ?ArrayCollection
    {
        return $this->customFields->filter(
            static fn (CustomField $customField) => $customField->getIsUniqueIdentifier()
        );
    }

    /**
     * Called when the custom fields are loaded from the database.
     */
    public function createFieldsSnapshot()
    {
        foreach ($this->customFields as $customField) {
            $this->initialCustomFields[$customField->getId()] = $customField->toArray();
        }
    }

    /**
     * Called before CustomObjectSave. It will record changes that happened for custom fields.
     */
    public function recordCustomFieldChanges()
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
