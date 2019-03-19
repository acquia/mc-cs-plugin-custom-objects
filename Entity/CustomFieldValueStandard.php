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

use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

abstract class CustomFieldValueStandard implements CustomFieldValueInterface
{
    /**
     * @var CustomField
     */
    protected $customField;

    /**
     * @var CustomItem
     */
    protected $customItem;

    /**
     * Flag to know whether to update this entity manually or let EntityManager to handle it.
     *
     * @var bool
     */
    protected $updateManually = false;

    /**
     * @param CustomField $customField
     * @param CustomItem  $customItem
     */
    public function __construct(CustomField $customField, CustomItem $customItem)
    {
        $this->customField = $customField;
        $this->customItem  = $customItem;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('customField', new Assert\NotBlank());
        $metadata->addPropertyConstraint('customItem', new Assert\NotBlank());
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->customField->getId();
    }

    /**
     * @return CustomField
     */
    public function getCustomField(): CustomField
    {
        return $this->customField;
    }

    /**
     * @return CustomItem
     */
    public function getCustomItem(): CustomItem
    {
        return $this->customItem;
    }

    /**
     * @param ClassMetadataBuilder $builder
     */
    protected static function addReferenceColumns(ClassMetadataBuilder $builder): void
    {
        $builder->createManyToOne('customField', CustomField::class)
            ->addJoinColumn('custom_field_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('customItem', CustomItem::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();
    }
}
