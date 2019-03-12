<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCompany;
use Mautic\LeadBundle\Entity\Company;
use DateTimeImmutable;

class CustomItemXrefCompanyTest extends \PHPUnit_Framework_TestCase
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
        $this->assertSame($dateAdded, $xref->getDateAdded());
    }
}
