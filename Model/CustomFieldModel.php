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

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Mautic\CoreBundle\Helper\UserHelper;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;

class CustomFieldModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    /**
     * @param EntityManager $entityManager
     * @param CustomFieldRepository $customFieldRepository
     * @param CustomFieldPermissionProvider $permissionProvider
     * @param UserHelper $userHelper
     */
    public function __construct(
        EntityManager $entityManager,
        CustomFieldRepository $customFieldRepository,
        CustomFieldPermissionProvider $permissionProvider,
        UserHelper $userHelper
    )
    {
        $this->entityManager          = $entityManager;
        $this->customFieldRepository = $customFieldRepository;
        $this->permissionProvider     = $permissionProvider;
        $this->userHelper             = $userHelper;
    }

    /**
     * @param CustomField $entity
     * 
     * @return CustomField
     */
    public function save(CustomField $entity): CustomField
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
     * @return CustomField
     * 
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomField
    {
        $entity = parent::getEntity($id);

        if (null === $entity) {
            throw new NotFoundException("Custom Field with ID = {$id} was not found");
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
        return $this->customFieldRepository;
    }

    /**
     * Used only by Mautic's generic methods. Use CustomFieldPermissionProvider instead.
     * 
     * @return string
     */
    public function getPermissionBase(): string
    {
        return 'custom_fields:custom_fields';
    }

    /**
     * @param CustomField $entity
     * 
     * @return CustomFieldt
     */
    private function sanitizeAlias(CustomField $entity): CustomField
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
     * @param CustomField $entity
     * 
     * @return CustomField
     */
    private function ensureUniqueAlias(CustomField $entity): CustomField
    {
        $testAlias = $entity->getAlias();
        $isUnique  = $this->customFieldRepository->isAliasUnique($testAlias, $entity->getId());
        $counter   = 1;

        while ($isUnique) {
            $testAlias = $testAlias.$counter;
            $isUnique  = $this->customFieldRepository->isAliasUnique($testAlias, $entity->getId());
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
