<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use DateTimeImmutable;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCustomItem;
use UnexpectedValueException;

class CustomItemXrefCustomItemTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersIfCustomItemAIsLower(): void
    {
        $customItemA = $this->createMock(CustomItem::class);
        $customItemB = $this->createMock(CustomItem::class);
        $dateAdded   = new DateTimeImmutable('2019-03-04 12:34:56');

        $customItemA->method('getId')->willReturn(33);
        $customItemB->method('getId')->willReturn(55);

        $xref = new CustomItemXrefCustomItem(
            $customItemA,
            $customItemB,
            $dateAdded
        );

        $this->assertSame($customItemA, $xref->getCustomItemLower());
        $this->assertSame($customItemA, $xref->getCustomItem());
        $this->assertSame($customItemB, $xref->getCustomItemHigher());
        $this->assertSame($customItemB, $xref->getLinkedEntity());
        $this->assertSame($dateAdded, $xref->getDateAdded());
    }

    public function testGettersIfCustomItemBIsLower(): void
    {
        $customItemA = $this->createMock(CustomItem::class);
        $customItemB = $this->createMock(CustomItem::class);
        $dateAdded   = new DateTimeImmutable('2019-03-04 12:34:56');

        $customItemA->method('getId')->willReturn(55);
        $customItemB->method('getId')->willReturn(33);

        $xref = new CustomItemXrefCustomItem(
            $customItemA,
            $customItemB,
            $dateAdded
        );

        $this->assertSame($customItemB, $xref->getCustomItemLower());
        $this->assertSame($customItemA, $xref->getCustomItemHigher());
        $this->assertSame($dateAdded, $xref->getDateAdded());
    }

    public function testGettersIfCustomItemsAreEqual(): void
    {
        $customItemA = $this->createMock(CustomItem::class);
        $customItemB = $this->createMock(CustomItem::class);
        $dateAdded   = new DateTimeImmutable('2019-03-04 12:34:56');

        $customItemA->method('getId')->willReturn(55);
        $customItemB->method('getId')->willReturn(55);

        $this->expectException(UnexpectedValueException::class);

        new CustomItemXrefCustomItem(
            $customItemA,
            $customItemB,
            $dateAdded
        );
    }
}
