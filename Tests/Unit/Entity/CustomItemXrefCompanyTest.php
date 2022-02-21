<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use DateTimeImmutable;
use Mautic\LeadBundle\Entity\Company;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCompany;

class CustomItemXrefCompanyTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $customItem = $this->createMock(CustomItem::class);
        $company    = $this->createMock(Company::class);
        $dateAdded  = new DateTimeImmutable('2019-03-04 12:34:56');
        $xref       = new CustomItemXrefCompany(
            $customItem,
            $company,
            $dateAdded
        );

        $this->assertSame($customItem, $xref->getCustomItem());
        $this->assertSame($company, $xref->getCompany());
        $this->assertSame($company, $xref->getLinkedEntity());
        $this->assertSame($dateAdded, $xref->getDateAdded());
    }
}
