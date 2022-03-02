<?php

namespace MauticPlugin\CustomObjectsBundle\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Exception\PermissionNotFoundException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Security\Permissions\CustomObjectPermissions;

class CustomItemListeningExtension implements QueryCollectionExtensionInterface
{
    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var CorePermissions
     */
    private $security;

    public function __construct(UserHelper $userHelper, CorePermissions $security)
    {
        $this->userHelper = $userHelper;
        $this->security   = $security;
    }

    /**
     * Limit just items that the user can view.
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (CustomItem::class !== $resourceClass) {
            return;
        }

        $userEntity = $this->userHelper->getUser();

        if ($userEntity->isAdmin()) {
            return;
        }

        $customObjectActivePermissions = [];
        $activePermissions             = ($userEntity instanceof \Mautic\UserBundle\Entity\User) ? $userEntity->getActivePermissions() : [];
        if (array_key_exists('custom_objects', $activePermissions)) {
            $customObjectActivePermissions = $activePermissions['custom_objects'];
        }

        $permissions = $this->security->getPermissionObjects();
        if (array_key_exists('custom_objects', $permissions)) {
            /* @var $customObjectPermissionsObject CustomObjectPermissions|null */
            $customObjectPermissionsObject = $permissions['custom_objects'];
            $customObjectPermissions       = $customObjectPermissionsObject->getPermissions();
        } else {
            throw new PermissionNotFoundException();
        }

        $allowedCustomObjectIds = [];
        foreach ($customObjectPermissions as $customObjectKey => $customObjectPermission) {
            if (!is_int($customObjectKey)) {
                continue;
            }
            if (!array_key_exists($customObjectKey, $customObjectActivePermissions)) {
                continue;
            }
            $userPermission = $customObjectActivePermissions[$customObjectKey];
            $viewPermission = $customObjectPermission['viewother'];
            $fullPermission = $customObjectPermission['full'];
            if (($userPermission & $viewPermission) || ($userPermission & $fullPermission)) {
                $allowedCustomObjectIds[] = $customObjectKey;
            }
        }

        $queryBuilder
            ->andWhere('o.customObject IN (:allowedCustomObjectIds)')
            ->setParameter('allowedCustomObjectIds', $allowedCustomObjectIds);
    }
}
