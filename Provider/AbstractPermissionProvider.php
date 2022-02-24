<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Provider;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

abstract class AbstractPermissionProvider
{
    public const BASE = 'undefined';

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
    public function isGranted(string $permission): void
    {
        if (!$this->corePermissions->isGranted(static::BASE.$permission)) {
            throw new ForbiddenException($permission);
        }
    }

    /**
     * @throws ForbiddenException
     */
    public function hasEntityAccess(string $permission, FormEntity $entity): void
    {
        if (!$this->corePermissions->hasEntityAccess(static::BASE.$permission.'own', static::BASE.$permission.'other', $entity->getCreatedBy())) {
            $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;

            throw new ForbiddenException($permission, get_class($entity), $entityId);
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
     * @throws ForbiddenException
     */
    public function canView(FormEntity $entity): void
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
     * @throws ForbiddenException
     */
    public function canEdit(FormEntity $entity): void
    {
        $this->hasEntityAccess('edit', $entity);
    }

    /**
     * @throws ForbiddenException
     */
    public function canClone(FormEntity $entity): void
    {
        // Check the create permission as new entity will be created.
        $this->isGranted('create');

        // But check also if the user can view others as clone will show values of the original entity.
        $this->hasEntityAccess('view', $entity);
    }

    /**
     * @throws ForbiddenException
     */
    public function canDelete(FormEntity $entity): void
    {
        $this->hasEntityAccess('delete', $entity);
    }
}
