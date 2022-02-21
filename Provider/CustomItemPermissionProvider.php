<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Security\Permissions\CustomObjectPermissions;

class CustomItemPermissionProvider
{
    /**
     * @var CorePermissions
     */
    private $corePermissions;

    public function __construct(CorePermissions $corePermissions)
    {
        $this->corePermissions = $corePermissions;
    }

    /**
     * @throws ForbiddenException
     */
    public function isGranted(string $permission, int $customObjectId): void
    {
        if (!$this->corePermissions->isGranted($this->getPermissionName($customObjectId, $permission))) {
            throw new ForbiddenException($permission, 'Items for Custom Object', $customObjectId);
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function hasEntityAccess(string $permission, CustomItem $entity): void
    {
        $permissionName = $this->getPermissionName($entity->getCustomObject()->getId(), $permission);
        if (!$this->corePermissions->hasEntityAccess("{$permissionName}own", "{$permissionName}other", $entity->getCreatedBy())) {
            throw new ForbiddenException($permission, 'CustomItem', $entity->getId());
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function canCreate(int $customObjectId): void
    {
        $this->isGranted('create', $customObjectId);
    }

    /**
     * @throws ForbiddenException
     */
    public function canView(CustomItem $entity): void
    {
        $this->hasEntityAccess('view', $entity);
    }

    /**
     * @throws ForbiddenException
     */
    public function canViewAtAll(int $customObjectId): void
    {
        $this->isGranted('view', $customObjectId);
    }

    /**
     * @throws ForbiddenException
     */
    public function canEdit(CustomItem $entity): void
    {
        $this->hasEntityAccess('edit', $entity);
    }

    /**
     * @throws ForbiddenException
     */
    public function canClone(CustomItem $entity): void
    {
        // Check the create permission as new entity will be created.
        $this->isGranted('create', $entity->getCustomObject()->getId());

        // But check also if the user can view others as clone will show values of the original entity.
        $this->hasEntityAccess('view', $entity);
    }

    /**
     * @throws ForbiddenException
     */
    public function canDelete(CustomItem $entity): void
    {
        $this->hasEntityAccess('delete', $entity);
    }

    private function getPermissionName(int $customObjectId, string $permission): string
    {
        return sprintf('%s:%d:%s', CustomObjectPermissions::NAME, $customObjectId, $permission);
    }
}
