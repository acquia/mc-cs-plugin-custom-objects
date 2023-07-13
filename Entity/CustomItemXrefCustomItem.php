<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefCustomItemRepository;
use UnexpectedValueException;

/**
 * As the {custom item} - {custom item} table can store the IDs both ways (higher - lower, lower - higher)
 * and both ways are valid, let's create a rule that one the first column will always contain the lower
 * ID and the second always the higher to avoid duplicates. As an example:.
 *
 * 23 - 44 is correct
 * 44 - 23 is not correct according to the rule.
 */
class CustomItemXrefCustomItem implements CustomItemXrefInterface
{
    public const TABLE_ALIAS = 'CustomItemXrefCustomItem';

    /**
     * @var CustomItem
     */
    private $customItemLower;

    /**
     * @var CustomItem
     */
    private $customItemHigher;

    /**
     * @var DateTimeInterface
     */
    private $dateAdded;

    /**
     * @throws UnexpectedValueException
     */
    public function __construct(CustomItem $customItemA, CustomItem $customItemB, ?DateTimeInterface $dateAdded = null)
    {
        if ($customItemA->getId() && $customItemA->getId() === $customItemB->getId()) {
            throw new UnexpectedValueException('It is not possible to link identical custom item.');
        }

        if ($customItemA->getId() < $customItemB->getId()) {
            $this->customItemLower  = $customItemA;
            $this->customItemHigher = $customItemB;
        } else {
            $this->customItemLower  = $customItemB;
            $this->customItemHigher = $customItemA;
        }

        $this->dateAdded = $dateAdded ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('custom_item_xref_custom_item');
        $builder->setCustomRepositoryClass(CustomItemXrefCustomItemRepository::class);

        $builder->createManyToOne('customItemLower', CustomItem::class)
            ->addJoinColumn('custom_item_id_lower', 'id', false, false, 'CASCADE')
            ->inversedBy('customItemLowerReferences')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('customItemHigher', CustomItem::class)
            ->addJoinColumn('custom_item_id_higher', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('dateAdded', Types::DATETIME_MUTABLE)
            ->columnName('date_added')
            ->build();
    }

    /**
     * @return CustomItem
     */
    public function getCustomItem()
    {
        return $this->getCustomItemLower();
    }

    /**
     * @return CustomItem
     */
    public function getLinkedEntity()
    {
        return $this->getCustomItemHigher();
    }

    /**
     * @return CustomItem
     */
    public function getCustomItemLower()
    {
        return $this->customItemLower;
    }

    /**
     * @return CustomItem
     */
    public function getCustomItemHigher()
    {
        return $this->customItemHigher;
    }

    /**
     * @return DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Take the ref custom item that has different ID than the provided item. Can be either lower or higher.
     */
    public function getCustomItemLinkedTo(CustomItem $customItem): CustomItem
    {
        return $this->getCustomItemHigher()->getId() === $customItem->getId()
        ? $this->getCustomItemLower()
        : $this->getCustomItemHigher();
    }
}
