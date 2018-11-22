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

namespace MauticPlugin\CustomObjectsBundle\Model;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Mautic\CoreBundle\Helper\UserHelper;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomObjectModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var CustomObjectPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param EntityManager $entityManager
     * @param CustomObjectRepository $customObjectRepository
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param UserHelper $userHelper
     */
    public function __construct(
        EntityManager $entityManager,
        CustomObjectRepository $customObjectRepository,
        CustomObjectPermissionProvider $permissionProvider,
        UserHelper $userHelper
    )
    {
        $this->entityManager          = $entityManager;
        $this->customObjectRepository = $customObjectRepository;
        $this->permissionProvider     = $permissionProvider;
        $this->userHelper             = $userHelper;
    }

    /**
     * @param CustomObject $entity
     * 
     * @return CustomObject
     */
    public function save(CustomObject $entity): CustomObject
    {
        $user   = $this->userHelper->getUser();
        $entity = $this->sanitizeAlias($entity);
        $entity = $this->ensureUniqueAlias($entity);
        $now    = new DateTimeHelper();

        if ($entity->isNew()) {
            $entity->setCreatedBy($user->getId());
            $entity->setCreatedByUser($user->getName());
            $entity->setDateAdded($now->getUtcDateTime());
        }

        $entity->setModifiedBy($user->getId());
        $entity->setModifiedByUser($user->getName());
        $entity->setDateModified($now->getUtcDateTime());

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param integer $id
     * 
     * @return CustomObject
     * 
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomObject
    {
        $entity = parent::getEntity($id);

        if (null === $entity) {
            throw new NotFoundException("Custom Object with ID = {$id} was not found");
        }

        return $entity;
    }

    /**
     * @param array $args
     * 
     * @return Paginator
     */
    public function fetchEntities(array $args = []): Paginator
    {
        return parent::getEntities($this->addCreatorLimit($args));
    }

    /**
     * Used only by Mautic's generic methods. Use DI instead.
     * 
     * @return CommonRepository
     */
    public function getRepository(): CommonRepository
    {
        return $this->customObjectRepository;
    }

    /**
     * Used only by Mautic's generic methods. Use CustomFieldPermissionProvider instead.
     * 
     * @return string
     */
    public function getPermissionBase(): string
    {
        return 'custom_objects:custom_objects';
    }

    /**
     * @param CustomObject $entity
     * 
     * @return CustomObject
     */
    private function sanitizeAlias(CustomObject $entity): CustomObject
    {
        $dirtyAlias = $entity->getAlias();

        if (empty($dirtyAlias)) {
            $dirtyAlias = $entity->getName();
        }

        $cleanAlias = $this->cleanAlias($dirtyAlias, '', false, '-');

        $entity->setAlias($cleanAlias);

        return $entity;
    }

    /**
     * Make sure alias is not already taken.
     *
     * @param CustomObject $entity
     * 
     * @return CustomObject
     */
    private function ensureUniqueAlias(CustomObject $entity): CustomObject
    {
        $testAlias = $entity->getAlias();
        $isUnique  = $this->customObjectRepository->isAliasUnique($testAlias, $entity->getId());
        $counter   = 1;

        while ($isUnique) {
            $testAlias = $testAlias.$counter;
            $isUnique  = $this->customObjectRepository->isAliasUnique($testAlias, $entity->getId());
            ++$counter;
        }

        if ($testAlias !== $entity->getAlias()) {
            $entity->setAlias($testAlias);
        }

        return $entity;
    }

    /**
     * Adds condition for creator if the user doesn't have permissions to view other.
     *
     * @param array $args
     * 
     * @return array
     */
    private function addCreatorLimit(array $args): array
    {
        try {
            $this->permissionProvider->isGranted('viewother');
        } catch (ForbiddenException $e) {
            if (!isset($args['filter'])) {
                $args['filter'] = [];
            }

            if (!isset($args['filter']['force'])) {
                $args['filter']['force'] = [];
            }

            $limitOwnerFilter = [
                [
                    'column' => 'e.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->userHelper->getUser()->getId(),
                ],
            ];

            $args['filter']['force'] = $args['filter']['force'] + $limitOwnerFilter;
        }

        return $args;
    }
}
