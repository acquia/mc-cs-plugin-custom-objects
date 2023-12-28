<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UpsertInterface;
use Mautic\CoreBundle\Entity\UpsertTrait;
use Mautic\CoreBundle\Helper\ArrayHelper;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={""},
 *          "post"={"security"="'custom_objects:[customObject]:create'"}
 *     },
 *     itemOperations={
 *          "get"={"security"="'custom_objects:[customObject]:view'"},
 *          "put"={"security"="'custom_objects:[customObject]:edit'"},
 *          "patch"={"security"="'custom_objects:[customObject]:edit'"},
 *          "delete"={"security"="'custom_objects:[customObject]:delete'"}
 *     },
 *     shortName="custom_items",
 *     normalizationContext={"groups"={"custom_item:read"}, "swagger_definition_name"="Read"},
 *     denormalizationContext={"groups"={"custom_item:write"}, "swagger_definition_name"="Write"}
 * )
 */
class CustomItem extends FormEntity implements UniqueEntityInterface, UpsertInterface
{
    use UpsertTrait;
    public const TABLE_NAME  = 'custom_item';
    public const TABLE_ALIAS = 'CustomItem';

    /**
     * @var int|null
     * @Groups({"custom_item:read"})
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
     * @Groups({"custom_item:read", "custom_item:write"})
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
    private $name;

    /**
     * @var CustomObject
     * @ManyToOne(targetEntity="CustomObject")
     * @JoinColumn(name="custom_object_id", referencedColumnName="id")
     * @Groups({"custom_item:read", "custom_item:write"})
     */
    private $customObject;

    /**
     * @var CustomItem|null
     */
    private $childCustomItem;

    /**
     * @var string|null
     * @Groups({"custom_item:read", "custom_item:write"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "maxLength"=191,
     *             "example"="en"
     *         }
     *     }
     * )
     */
    private $language;

    /**
     * @var Category|null
     * @ManyToOne(targetEntity="Category")
     * @JoinColumn(name="category_id", referencedColumnName="id")
     * @ApiProperty(readableLink=false, writableLink=false)
     * @Groups({"custom_item:read", "custom_item:write"})
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $customFieldValues;

    /**
     * @var array
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="array",
     *             "items"={
     *                 "type"="object",
     *                 "properties"={
     *                     "id"={
     *                         "type"="string"
     *                     },
     *                     "value"={
     *                         "type"="object",
     *                         "additionalProperties"={
     *                             "oneOf"={
     *                                 {"type"="string"},
     *                                 {"type"="number"},
     *                                 {"type"="boolean"},
     *                                 {"type"="array"}
     *                             }
     *                         }
     *                     }
     *                 }
     *             }
     *         }
     *     }
     * )
     * @Groups({"custom_item:read", "custom_item:write"})
     */
    private $fieldValues;

    /**
     * @var mixed[]
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
     * @var ArrayCollection
     */
    private $customItemLowerReferences;

    private ?string $uniqueHash = null;

    public function __construct(CustomObject $customObject)
    {
        $this->customObject              = $customObject;
        $this->customFieldValues         = new ArrayCollection();
        $this->contactReferences         = new ArrayCollection();
        $this->companyReferences         = new ArrayCollection();
        $this->customItemLowerReferences = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(CustomItemRepository::class)
            ->addFulltextIndex(['name'], 'name_fulltext');

        $builder->createManyToOne('customObject', CustomObject::class)
            ->addJoinColumn('custom_object_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createField('uniqueHash', Types::STRING)
            ->columnName('unique_hash')
            ->unique()
            ->nullable()
            ->build();

        $builder->createOneToMany('contactReferences', CustomItemXrefContact::class)
            ->mappedBy('customItem')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('companyReferences', CustomItemXrefCompany::class)
            ->mappedBy('customItem')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('customItemLowerReferences', CustomItemXrefCustomItem::class)
            ->mappedBy('customItemLower')
            ->fetchExtraLazy()
            ->build();

        $builder->addBigIntIdField();
        $builder->addCategory();
        $builder->addField('name', Type::STRING);
        $builder->addNullableField('language', Type::STRING, 'lang');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
        $metadata->addPropertyConstraint('name', new Assert\Length(['max' => 255]));
    }

    /**
     * @param mixed[] $data
     */
    public function populateFromArray(array $data): void
    {
        foreach ($data as $property => $value) {
            $camelCaseProperty          = lcfirst(ucwords($property, '_'));
            $this->{$camelCaseProperty} = $value;
        }
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return CustomObject
     */
    public function getCustomObject()
    {
        return $this->customObject;
    }

    public function setCustomObject(CustomObject $customObject): void
    {
        $this->customObject = $customObject;
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
        $this->isChanged('category', $category ? $category : null);
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

    public function setChildCustomItem(CustomItem $childCustomItem): void
    {
        $this->childCustomItem = $childCustomItem;
    }

    public function getChildCustomItem(): ?CustomItem
    {
        return $this->childCustomItem;
    }

    public function getChildCustomFieldValues(): ArrayCollection
    {
        if ($this->childCustomItem) {
            return $this->childCustomItem->getCustomFieldValues();
        }

        return new ArrayCollection();
    }

    public function generateNameForChildObject(string $entityType, int $entityId, CustomItem $parentCustomItem): void
    {
        $this->setName(
            "relationship-between-{$entityType}-{$entityId}-and-{$parentCustomItem->getCustomObject()->getAlias()}-{$parentCustomItem->getId()}"
        );
    }

    /**
     * @param CustomFieldValueInterface $customFieldValue
     */
    public function addCustomFieldValue($customFieldValue)
    {
        if (null === $this->customFieldValues) {
            $this->customFieldValues = new ArrayCollection();
        }

        $this->customFieldValues->set($customFieldValue->getCustomField()->getId(), $customFieldValue);
    }

    /**
     * @param array $values
     *
     * @throws NotFoundException
     */
    public function setCustomFieldValues($values)
    {
        foreach ($values as $fieldName => $fieldValue) {
            try {
                $customFieldValue = $this->findCustomFieldValueForFieldAlias((string) $fieldName);
                $customFieldValue->setValue($fieldValue);
            } catch (NotFoundException $e) {
                $this->createNewCustomFieldValueByFieldAlias((string) $fieldName, $fieldValue);
            }
        }
        $this->setDefaultValuesForMissingFields();
    }

    /**
     * Called when the custom field values are loaded from the database.
     */
    public function createFieldValuesSnapshot()
    {
        foreach ($this->customFieldValues as $customFieldValue) {
            $this->initialCustomFieldValues[$customFieldValue->getCustomField()->getId()] = $customFieldValue->getValue();
        }
    }

    /**
     * Called before CustomItemSave. It will record changes that happened for custom field values.
     */
    public function recordCustomFieldValueChanges()
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
    public function getCustomFieldValues()
    {
        if (null === $this->customFieldValues) {
            $this->customFieldValues = new ArrayCollection();
        }

        return $this->customFieldValues;
    }

    /**
     * Just for API.
     */
    public function getFieldValues(): array
    {
        $fieldValues = [];
        if (null === $this->customFieldValues) {
            $this->customFieldValues = new ArrayCollection();
        }
        foreach ($this->customFieldValues as $customFieldValue) {
            $fieldValues[] =
                [
                    'id'    => strval($customFieldValue->getCustomField()->getId()),
                    'value' => $customFieldValue->getValue(),
                ];
        }

        return $fieldValues;
    }

    /**
     * Just for API.
     *
     * @throws NotFoundException
     */
    public function setFieldValues(array $values): void
    {
        if (null === $this->customFieldValues) {
            $this->customFieldValues = new ArrayCollection();
        }
        foreach ($values as $value) {
            try {
                $customFieldValue = $this->findCustomFieldValueForFieldId((int) $value['id']);
                $customFieldValue->setValue($value['value']);
            } catch (NotFoundException $e) {
                $this->createNewCustomFieldValueByFieldId((int) $value['id'], $value['value']);
            }
        }

        /**
         * We could have done it in CustomItemDataPersister::persist() by
         * injecting Symfony\Component\HttpFoundation\RequestStack and get request method.
         * Since, CustomItem entity has multiple public methods which could lead to BC break.
         * Hence, we are using $_SERVER here to get the request method type.
         */
        if ('PATCH' !== $_SERVER['REQUEST_METHOD']) {
            $this->setDefaultValuesForMissingFields();
        }
    }

    /**
     * @param int $customFieldId
     *
     * @return CustomFieldValueInterface
     *
     * @throws NotFoundException
     */
    public function findCustomFieldValueForFieldId($customFieldId)
    {
        $customFieldValue = $this->customFieldValues->get($customFieldId);

        if (!$customFieldValue) {
            throw new NotFoundException("Custom Field Value for ID = {$customFieldId} was not found.");
        }

        return $customFieldValue;
    }

    /**
     * @param string $customFieldAlias
     *
     * @return CustomFieldValueInterface
     *
     * @throws NotFoundException
     */
    public function findCustomFieldValueForFieldAlias($customFieldAlias)
    {
        $filteredValues = $this->customFieldValues->filter(function (CustomFieldValueInterface $customFieldValue) use ($customFieldAlias) {
            return $customFieldValue->getCustomField()->getAlias() === $customFieldAlias;
        });

        if (!$filteredValues->count()) {
            throw new NotFoundException("Custom Field Value for alias = {$customFieldAlias} was not found.");
        }

        return $filteredValues->first();
    }

    public function findChildCustomItem(): CustomItem
    {
        /** @var CustomItemXrefCustomItem|null $childXref */
        $childXref = $this->getCustomItemLowerReferences()
            ->filter(function (CustomItemXrefCustomItem $xref) {
                // The child custom item's object must have the same ID as the current custom item child object.
                return $xref->getCustomItemLinkedTo($this)->getCustomObject()->getMasterObject()->getId() === $this->getCustomObject()->getId();
            })->first();

        if ($childXref) {
            return $childXref->getCustomItemLinkedTo($this);
        }

        throw new NotFoundException("Custom item {$this->getId()} does not have a child custom item");
    }

    /**
     * @param mixed $value
     *
     * @throws NotFoundException
     */
    public function createNewCustomFieldValueByFieldId(int $customFieldId, $value): CustomFieldValueInterface
    {
        /** @var CustomField $customField */
        foreach ($this->getCustomObject()->getCustomFields() as $customField) {
            if ($customField->getId() === (int) $customFieldId) {
                $fieldType        = $customField->getTypeObject();
                $customFieldValue = $fieldType->createValueEntity($customField, $this, $value);
                $this->addCustomFieldValue($customFieldValue);

                return $customFieldValue;
            }
        }

        throw new NotFoundException("Custom field with ID {$customFieldId} was not found.");
    }

    /**
     * @param mixed $value
     *
     * @throws NotFoundException
     */
    public function createNewCustomFieldValueByFieldAlias(string $customFieldAlias, $value): CustomFieldValueInterface
    {
        /** @var CustomField $customField */
        foreach ($this->getCustomObject()->getCustomFields() as $customField) {
            if ($customField->getAlias() === $customFieldAlias) {
                $fieldType        = $customField->getTypeObject();
                $customFieldValue = $fieldType->createValueEntity($customField, $this, $value);
                $this->addCustomFieldValue($customFieldValue);

                return $customFieldValue;
            }
        }

        throw new NotFoundException("Custom field with alias {$customFieldAlias} was not found.");
    }

    public function setDefaultValuesForMissingFields(): void
    {
        $this->getCustomObject()->getCustomFields()->map(function (CustomField $customField) {
            try {
                $this->findCustomFieldValueForFieldId($customField->getId());
            } catch (NotFoundException $e) {
                $this->addCustomFieldValue(
                    $customField->getTypeObject()->createValueEntity($customField, $this, $customField->getDefaultValue())
                );
            }
        });
    }

    /**
     * @param CustomItemXrefInterface $reference
     */
    public function addContactReference($reference)
    {
        $this->contactReferences->add($reference);
    }

    /**
     * @return Collection
     */
    public function getContactReferences()
    {
        return $this->contactReferences;
    }

    /**
     * @param CustomItemXrefInterface $reference
     */
    public function addCompanyReference($reference)
    {
        $this->companyReferences->add($reference);
    }

    /**
     * @return Collection
     */
    public function getCompanyReferences()
    {
        return $this->companyReferences;
    }

    /**
     * @param CustomItemXrefInterface $reference
     */
    public function addCustomItemReference($reference)
    {
        $this->customItemLowerReferences->add($reference);
    }

    /**
     * @return Collection
     */
    public function getCustomItemLowerReferences()
    {
        return $this->customItemLowerReferences;
    }

    public function getRelationsByType(string $entityType): Collection
    {
        switch ($entityType) {
            case 'contact':
                return $this->getContactReferences();
            case 'company':
                return $this->getCompanyReferences();
            case 'customItem':
                return $this->getCustomItemLowerReferences();
            default:
                return new ArrayCollection([]);
        }
    }

    public function getUniqueHash(): ?string
    {
        return $this->uniqueHash;
    }

    public function setUniqueHash(?string $uniqueHash): void
    {
        $this->uniqueHash = $uniqueHash;
    }

    public function updateUniqueHash(): void
    {
        $uniqueHash             = [];
        $uniqueIdentifierFields = $this->customObject->getUniqueIdentifierFields();

        if (0 === $uniqueIdentifierFields->count()) {
            $this->setUniqueHash(null);

            return;
        }

        foreach ($uniqueIdentifierFields->getValues() as $uniqueIdentifierField) {
            $uniqueIdentifierFieldAlias              = $uniqueIdentifierField->getAlias();
            $uniqueHash[$uniqueIdentifierFieldAlias] = $this->findCustomFieldValueForFieldAlias($uniqueIdentifierFieldAlias)->getValue();
        }
        //To prevent creation of duplicates (in case of multiple unique ID fields) due to the order of key-values in the array
        // Eg. {id => 1, name => "Jay"} and {name => "Jay", id => 1} are duplicates
        ksort($uniqueHash);

        [] == $uniqueHash ? $this->setUniqueHash(null) : $this->setUniqueHash(hash('sha256', json_encode($uniqueHash)));
    }
}
