<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use DateTimeImmutable;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;

class CustomItemXrefContactTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $customItem = $this->createMock(CustomItem::class);
        $contact    = $this->createMock(Lead::class);
        $dateAdded  = new DateTimeImmutable('2019-03-04 12:34:56');
        $xref       = new CustomItemXrefContact(
            $customItem,
            $contact,
            $dateAdded
        );

        $this->assertSame($customItem, $xref->getCustomItem());
        $this->assertSame($contact, $xref->getContact());
        $this->assertSame($contact, $xref->getLinkedEntity());
        $this->assertSame($dateAdded, $xref->getDateAdded());
    }
}
