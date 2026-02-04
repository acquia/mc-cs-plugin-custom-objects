<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Serializer',
        'Report',
        'Extension'
    ];

    $services->load('MauticPlugin\\CustomObjectsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\CustomObjectsBundle\\Repository\\', '../Repository/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->alias('mautic.custom.model.field', MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel::class);
    $services->alias('mautic.custom.model.field.value', MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel::class);
    $services->alias('mautic.custom.model.item', MauticPlugin\CustomObjectsBundle\Model\CustomItemModel::class);
    $services->alias('mautic.custom.model.import.item', MauticPlugin\CustomObjectsBundle\Model\CustomItemImportModel::class);
    $services->alias('mautic.custom.model.import.xref.contact', MauticPlugin\CustomObjectsBundle\Model\CustomItemXrefContactModel::class);
    $services->alias('mautic.custom.model.field.option', MauticPlugin\CustomObjectsBundle\Model\CustomFieldOptionModel::class);
    $services->alias('mautic.custom.model.object', MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel::class);
    $services->alias('mautic.custom.model.export_scheduler', MauticPlugin\CustomObjectsBundle\Model\CustomItemExportSchedulerModel::class);

    $services->alias('custom_field.repository', MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository::class);
    $services->alias('custom_item.repository', MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository::class);
    $services->alias('custom_object.repository', MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository::class);
    $services->alias('custom_item.xref.contact.repository', MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefContactRepository::class);
    $services->alias('custom_item.xref.custom_item.repository',  MauticPlugin\CustomObjectsBundle\Repository\CustomItemXrefCustomItemRepository::class);
    $services->alias('custom_item_export_scheduler.repository', MauticPlugin\CustomObjectsBundle\Repository\CustomItemExportSchedulerRepository::class);
};
