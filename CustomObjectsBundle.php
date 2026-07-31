<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle;

use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use MauticPlugin\CustomObjectsBundle\DependencyInjection\Compiler\CustomFieldTypePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomObjectsBundle extends AbstractPluginBundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomFieldTypePass());
    }
}
