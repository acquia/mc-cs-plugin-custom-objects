<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\AllydeBundle\Tests\TestingTrait;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;

class DbalQueryTraitTest extends \PHPUnit\Framework\TestCase
{
    use TestingTrait;

    public function testExecuteSelectIfSelect(): void
    {
        $qb        = $this->createMock(QueryBuilder::class);
        $statement = $this->createMock(Statement::class);
        $trait     = $this->getMockForTrait(DbalQueryTrait::class);

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $this->assertSame($statement, $this->invokeMethod($trait, 'executeSelect', [$qb]));
    }

    public function testExecuteSelectIfNotSelect(): void
    {
        $qb    = $this->createMock(QueryBuilder::class);
        $trait = $this->getMockForTrait(DbalQueryTrait::class);

        $qb->expects($this->once())
            ->method('execute')
            ->willReturn(4);

        $this->expectException(\UnexpectedValueException::class);

        $this->invokeMethod($trait, 'executeSelect', [$qb]);
    }
}
