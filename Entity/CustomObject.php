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
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use MauticPlugin\CustomObjectsBundle\Entity\UniqueEntityInterface;

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
     * @var DateTimeInterface|null
     */
    private $dateAdded;

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

        $builder->setTable('custom_object')
            ->setCustomRepositoryClass(CustomObjectRepository::class);

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
}
