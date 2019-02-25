<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Provider;

use MauticPlugin\CustomObjectsBundle\Security\Permissions\CustomObjectPermissions;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomItemPermissionProvider
{
    /**
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * @param CorePermissions $corePermissions
     */
    public function __construct(CorePermissions $corePermissions)
    {
        $this->corePermissions = $corePermissions;
    }

    /**
     * @param string $permission
     * @param int $customObjectId
     *
     * @throws ForbiddenException
     */
    public function isGranted(string $permission, int $customObjectId): void
    {
        if (!$this->corePermissions->isGranted($this->getPermissionName($customObjectId, $permission))) {
            throw new ForbiddenException($permission);
        }
    }

    /**
     * @param string $permission
     * @param CustomItem $entity
     *
     * @throws ForbiddenException
     */
    public function hasEntityAccess(string $permission, CustomItem $entity): void
    {
        $permissionName = $this->getPermissionName($entity->getCustomObject()->getId(), $permission);
        if (!$this->corePermissions->hasEntityAccess("{$permissionName}own", "{$permissionName}other", $entity->getCreatedBy())) {
            throw new ForbiddenException($permission);
        }
    }

    /**
     * @param int $customObjectId
     *
     * @throws ForbiddenException
     */
    public function canCreate(int $customObjectId): void
    {
        $this->isGranted('create', $customObjectId);
    }

    /**
     * @param CustomItem $entity
     *
     * @throws ForbiddenException
     */
    public function canView(CustomItem $entity): void
    {
        $this->hasEntityAccess('view', $entity);
    }

    /**
     * @param int $customObjectId
     *
     * @throws ForbiddenException
     */
    public function canViewAtAll(int $customObjectId): void
    {
        $this->isGranted('view', $customObjectId);
    }

    /**
     * @param CustomItem $entity
     *
     * @throws ForbiddenException
     */
    public function canEdit(CustomItem $entity): void
    {
        $this->hasEntityAccess('edit', $entity);
    }

    /**
     * @param CustomItem $entity
     *
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
     * @param CustomItem $entity
     *
     * @throws ForbiddenException
     */
    public function canDelete(CustomItem $entity): void
    {
        $this->hasEntityAccess('delete', $entity);
    }

    /**
     * @param int $customObjectId
     * @param string $permission
     *
     * @return string
     */
    private function getPermissionName(int $customObjectId, string $permission): string
    {
        return sprintf('%s:%d:%s', CustomObjectPermissions::NAME, $customObjectId, $permission);
    }
}
