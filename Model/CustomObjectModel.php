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
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @param EntityManager                  $entityManager
     * @param CustomObjectRepository         $customObjectRepository
     * @param CustomObjectPermissionProvider $permissionProvider
     * @param UserHelper                     $userHelper
     * @param CustomFieldModel               $customFieldModel
     */
    public function __construct(
        EntityManager $entityManager,
        CustomObjectRepository $customObjectRepository,
        CustomObjectPermissionProvider $permissionProvider,
        UserHelper $userHelper,
        CustomFieldModel $customFieldModel
    ) {
        $this->entityManager          = $entityManager;
        $this->customObjectRepository = $customObjectRepository;
        $this->permissionProvider     = $permissionProvider;
        $this->userHelper             = $userHelper;
        $this->customFieldModel = $customFieldModel;
    }

    /**
     * @param CustomObject $entity
     * 
     * @return CustomObject
     */
    public function save(CustomObject $entity): CustomObject
    {
        $user = $this->userHelper->getUser();
        $now  = new DateTimeHelper();

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
     * @return Paginator|array
     */
    public function fetchEntities(array $args = [])
    {
        return parent::getEntities($this->addCreatorLimit($args));
    }

    /**
     * @return array
     */
    public function fetchAllPublishedEntities(): array
    {
        return $this->fetchEntities([
            'ignore_paginator' => true,
            'filter'           => [
                'force' => [
                    [
                        'column' => 'e.isPublished',
                        'value'  => true,
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ]);
    }

    public function removeCustomFieldById(CustomObject $customObject, int $customFieldId)
    {
        foreach($customObject->getCustomFields() as $customField) {
            if ($customField->getId() === $customFieldId) {
                $customObject->removeCustomField($customField);
                $this->customFieldModel->deleteEntity($customField);
            }
        }
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
