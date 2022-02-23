<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Repository;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Repository\DbalQueryTrait;

class DbalQueryTraitTest extends \PHPUnit\Framework\TestCase
{
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

    /**
     * @param mixed[] $args
     *
     * @return mixed
     */
    private function invokeMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
