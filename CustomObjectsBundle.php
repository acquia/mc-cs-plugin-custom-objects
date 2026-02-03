<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle;


use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use MauticPlugin\CustomObjectsBundle\DependencyInjection\Compiler\CustomFieldTypePass;

class CustomObjectsBundle extends PluginBundleBase
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomFieldTypePass());
    }
}
