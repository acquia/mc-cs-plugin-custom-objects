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
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Mautic\LeadBundle\Entity\Lead;
use DateTimeInterface;
use DateTimeImmutable;
use DateTimeZone;

class CustomItemXrefContact
{
    const ALIAS = 'cixc';
    
    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var CustomItem
     */
    private $customItem;

    /**
     * @var DateTimeInterface
     */
    private $dateAdded;

    /**
     * @param CustomItem             $customItem
     * @param Lead                   $contact
     * @param DateTimeInterface|null $dateAdded
     */
    public function __construct(CustomItem $customItem, string $entityType, int $entityId, ?DateTimeInterface $dateAdded = null)
    {
        $this->customItem = $customItem;
        $this->contact    = $contact;
        $this->dateAdded  = $dateAdded ?: new DateTimeImmutable(new DateTimeZone('UTC'));
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
            ->makePrimaryKey()
            ->build();

        $builder->createManyToOne('contact', Lead::class)
            ->addJoinColumn('contact_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->build();

        $builder->createField('dateAdded', Type::DATETIME)
            ->columnName('date_added')
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('enityType', new Assert\Length(['max' => 255]));
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
     * @return DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }
}
