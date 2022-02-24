<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Company;

class CustomItemXrefCompany implements CustomItemXrefInterface
{
    public const TABLE_NAME  = 'custom_item_xref_company';
    public const TABLE_ALIAS = 'CustomItemXrefCompany';

    /**
     * @var Company
     */
    private $company;

    /**
     * @var CustomItem
     */
    private $customItem;

    /**
     * @var DateTimeInterface
     */
    private $dateAdded;

    public function __construct(CustomItem $customItem, Company $company, ?DateTimeInterface $dateAdded = null)
    {
        $this->customItem = $customItem;
        $this->company    = $company;
        $this->dateAdded  = $dateAdded ?: new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(static::TABLE_NAME);

        $builder->createManyToOne('customItem', CustomItem::class)
            ->addJoinColumn('custom_item_id', 'id', false, false, 'CASCADE')
            ->inversedBy('companyReferences')
            ->makePrimaryKey()
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('company', Company::class)
            ->addJoinColumn('company_id', 'id', false, false, 'CASCADE')
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
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return Company
     */
    public function getLinkedEntity()
    {
        return $this->getCompany();
    }

    /**
     * @return DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }
}
