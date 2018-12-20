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

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Entity\UniqueEntityInterface;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomFieldPermissionProvider
{
    public const BASE = 'custom_objects:custom_objects:';

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
     * 
     * @throws ForbiddenException
     */
    public function isGranted(string $permission): void
    {
        if (!$this->corePermissions->isGranted(CustomObjectPermissionProvider::BASE.$permission)) {
            throw new ForbiddenException($permission);
        }
    }

    /**
     * @param string $permission
     * 
     * @throws ForbiddenException
     */
    public function hasEntityAccess(string $permission, UniqueEntityInterface $entity): void
    {
        if (!$this->corePermissions->hasEntityAccess(self::BASE.$permission.'own', self::BASE.$permission.'other', $entity->getCreatedBy())) {
            throw new ForbiddenException($permission);
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function canCreate(): void
    {
        $this->isGranted('create');
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @throws ForbiddenException
     */
    public function canView(UniqueEntityInterface $entity): void
    {
        $this->hasEntityAccess('view', $entity);
    }

    /**
     * @throws ForbiddenException
     */
    public function canViewAtAll(): void
    {
        $this->isGranted('view');
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @throws ForbiddenException
     */
    public function canEdit(UniqueEntityInterface $entity): void
    {
        $this->hasEntityAccess('edit', $entity);
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @throws ForbiddenException
     */
    public function canClone(UniqueEntityInterface $entity): void
    {
        // Check the create permission as new entity will be created.
        $this->isGranted('create');

        // But check also if the user can view others as clone will show values of the original entity.
        $this->hasEntityAccess('view', $entity);
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @throws ForbiddenException
     */
    public function canDelete(UniqueEntityInterface $entity): void
    {
        $this->hasEntityAccess('delete', $entity);
    }
}
