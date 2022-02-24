<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CustomFieldTypePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        /** @var Definition $customFieldTypeProvider */
        $customFieldTypeProvider = $container->findDefinition('custom_field.type.provider');
        $customFieldTypeDiKeys   = array_keys($container->findTaggedServiceIds('custom.field.type'));

        foreach ($customFieldTypeDiKeys as $id) {
            $customFieldType = $container->findDefinition($id);
            $customFieldTypeProvider->addMethodCall('addType', [$customFieldType]);
        }
    }
}
