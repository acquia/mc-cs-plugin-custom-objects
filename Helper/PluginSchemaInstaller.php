<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Creates the plugin's tables from its Doctrine metadata.
 *
 * The plugin install/update events carry only the Plugin entity - the $metadata and
 * $installedSchema arguments are handed to the deprecated static hooks on
 * PluginBundleBase, never to the events. So both have to be derived here.
 */
class PluginSchemaInstaller
{
    private const ENTITY_NAMESPACE = 'MauticPlugin\\CustomObjectsBundle\\Entity\\';

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Creates any of the plugin's tables that are not in the database yet. Doing
     * nothing when they all exist keeps this safe to call on every install and update.
     */
    public function ensureTablesExist(): void
    {
        $connection = $this->entityManager->getConnection();
        $schema     = $connection->createSchemaManager()->introspectSchema();

        $missing = array_values(array_filter(
            $this->getPluginMetadata(),
            fn (ClassMetadata $metadata): bool => !$schema->hasTable($metadata->getTableName())
        ));

        if ([] === $missing) {
            return;
        }

        $schemaTool = new SchemaTool($this->entityManager);

        foreach ($schemaTool->getCreateSchemaSql($missing) as $query) {
            $connection->executeStatement($query);
        }
    }

    /**
     * @return array<ClassMetadata<object>>
     */
    private function getPluginMetadata(): array
    {
        return array_filter(
            $this->entityManager->getMetadataFactory()->getAllMetadata(),
            fn (ClassMetadata $metadata): bool => str_starts_with($metadata->getName(), self::ENTITY_NAMESPACE)
                && !$metadata->isMappedSuperclass
                && !$metadata->isEmbeddedClass
        );
    }
}
