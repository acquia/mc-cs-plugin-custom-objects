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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use DateTimeInterface;
use DateTimeImmutable;
use DateTimeZone;

class CustomItemXrefCustomItem
{
    /**
     * @var CustomItem
     */
    private $customItem;

    /**
     * @var CustomItem
     */
    private $parentCustomItem;

    /**
     * @var DateTimeInterface
     */
    private $dateAdded;

    /**
     * @param CustomItem             $customItem
     * @param CustomItem             $parentCustomItem
     * @param DateTimeInterface|null $dateAdded
     */
    public function __construct(CustomItem $customItem, CustomItem $parentCustomItem, ?DateTimeInterface $dateAdded = null)
    {
        $this->customItem       = $customItem;
        $this->parentCustomItem = $parentCustomItem;
        $this->dateAdded        = $dateAdded ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('custom_item_xref_contact');

        $builder->createManyToOne('customItem', CustomItem::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->inversedBy('contactReferences')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('parentCustomItem', CustomItem::class)
            ->addJoinColumn('parent_custom_item_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('dateAdded', Type::DATETIME)
            ->columnName('date_added')
            ->build();
    }

    /**
     * @return CustomItem
     */
    public function getCustomItem()
    {
        return $this->customItem;
    }

    /**
     * @return CustomItem
     */
    public function getParentCustomItem()
    {
        return $this->parentCustomItem;
    }

    /**
     * @return DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }
}
