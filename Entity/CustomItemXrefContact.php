<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository;

class CustomItemXrefContact implements CustomItemXrefInterface
{
    public const TABLE_NAME  = 'custom_item_xref_contact';
    public const TABLE_ALIAS = 'CustomItemXrefContact';

    private \DateTimeInterface $dateAdded;

    public function __construct(
        private CustomItem $customItem,
        private Lead $contact,
        ?\DateTimeInterface $dateAdded = null
    ) {
        $this->dateAdded  = $dateAdded ?: new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME);
        $builder->setCustomRepositoryClass(CustomItemXrefContactRepository::class);

        $builder->createManyToOne('customItem', CustomItem::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->inversedBy('contactReferences')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('contact', Lead::class)
            ->addJoinColumn('contact_id', 'id', false, false, 'CASCADE')
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
        return $this->customItem;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return Lead
     */
    public function getLinkedEntity()
    {
        return $this->getContact();
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }
}
