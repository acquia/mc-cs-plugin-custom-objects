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

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\DependencyInjection\Compiler;

use MauticPlugin\CustomObjectsBundle\DependencyInjection\Compiler\CustomFieldTypePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CustomFieldTypePassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess(): void
    {
        $containerBuilder    = $this->createMock(ContainerBuilder::class);
        $definition          = $this->createMock(Definition::class);
        $customFieldTypePass = new CustomFieldTypePass();

        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('custom.field.type')
            ->willReturn(['int.type' => [], 'text.type' => []]);

        $containerBuilder->expects($this->exactly(3))
            ->method('findDefinition')
            ->withConsecutive(
                ['custom_field.type.provider'],
                ['int.type'],
                ['text.type']
            )
            ->willReturnOnConsecutiveCalls(
                $definition
            );

        $definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addType'],
                ['addType']
            );

        $customFieldTypePass->process($containerBuilder);
    }
}
