<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\CustomObjectsBundle\Helper\ContactFilterMatcher;
use MauticPlugin\CustomObjectsBundle\Helper\FilterEvaluator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Provider/SessionProvider.php',
        'Report/ReportColumnsBuilder.php',
        'Serializer/ApiNormalizer.php',
        'Extension/CustomItemListeningExtension.php',
        // Registered explicitly in config.php so the int $leadCustomItemFetchLimit arg can be set
        'Helper/ContactFilterMatcher.php',
        'Helper/FilterEvaluator.php',
    ];

    $services->load('MauticPlugin\\CustomObjectsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\CustomObjectsBundle\\Repository\\', '../Repository/*Repository.php');

    // Aliases so autowiring resolves the config.php-registered services by class name
    $services->alias(ContactFilterMatcher::class, 'custom_object.helper.contact_filter_matcher')->public();
    $services->alias(FilterEvaluator::class, 'custom_object.helper.filter_evaluator')->public();
};
