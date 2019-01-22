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
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use MauticPlugin\CustomObjectsBundle\Entity\UniqueEntityInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Iterator\CustomFieldValues;
use Doctrine\Common\Collections\ArrayCollection;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCompany;

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
     * @var ArrayCollection
     */
    private $contactReferences;

    /**
     * @var ArrayCollection
     */
    private $companyReferences;

    public function __clone()
    {
        $this->id = null;
    }

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
            ->build();

        $builder->createOneToMany('contactReferences', CustomItemXrefContact::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->mappedBy('customItem')
            ->build();

        $builder->createOneToMany('companyReferences', CustomItemXrefCompany::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->mappedBy('customItem')
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
    public function getId()
    {
        return $this->id;
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
     * @return ArrayCollection
     */
    public function getContactReferences()
    {
        return $this->contactReferences;
    }
}
