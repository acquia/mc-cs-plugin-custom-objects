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

class CustomObjectPermissionProvider
{
    /**
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * @param CorePermissions $corePermissions
     */
    public function __construct(
        CorePermissions $corePermissions
    )
    {
        $this->corePermissions = $corePermissions;
    }

    /**
     * @thorws ForbiddenException
     */
    public function canCreate()
    {
        if (!$this->corePermissions->isGranted('custom_objects:custom_objects:create')) {
            throw new ForbiddenException('create');
        }
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @thorws ForbiddenException
     */
    public function canView(UniqueEntityInterface $entity)
    {
        if (!$this->corePermissions->hasEntityAccess('custom_objects:custom_objects:viewown', 'custom_objects:custom_objects:viewother', $entity->getCreatedBy())) {
            throw new ForbiddenException('view', $entity);
        }
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @thorws ForbiddenException
     */
    public function canEdit(UniqueEntityInterface $entity)
    {
        if (!$this->corePermissions->hasEntityAccess('custom_objects:custom_objects:editown', 'custom_objects:custom_objects:editother', $entity->getCreatedBy())) {
            throw new ForbiddenException('edit', $entity);
        }
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @thorws ForbiddenException
     */
    public function canClone(UniqueEntityInterface $entity)
    {
        // Check the create permission as new entity will be created.
        if (!$this->corePermissions->isGranted('custom_objects:custom_objects:create')) {
            throw new ForbiddenException('create');
        }

        // But check also if the user can view others as clone will show values of the original entity.
        if (!$this->corePermissions->hasEntityAccess('custom_objects:custom_objects:viewown', 'custom_objects:custom_objects:viewother', $entity->getCreatedBy())) {
            throw new ForbiddenException('view', $entity);
        }
    }

    /**
     * @param UniqueEntityInterface $entity
     * 
     * @thorws ForbiddenException
     */
    public function canDelete(UniqueEntityInterface $entity)
    {
        if (!$this->corePermissions->hasEntityAccess('custom_objects:custom_objects:deleteown', 'custom_objects:custom_objects:deleteother', $entity->getCreatedBy())) {
            throw new ForbiddenException('delete', $entity);
        }
    }
}
