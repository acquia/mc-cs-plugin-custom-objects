<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;

class CustomFieldModel extends FormModel
{
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var CustomFieldPermissionProvider
     */
    private $permissionProvider;

    public function __construct(
        CustomFieldRepository $customFieldRepository,
        CustomFieldPermissionProvider $permissionProvider,
        UserHelper $userHelper
    ) {
        $this->customFieldRepository = $customFieldRepository;
        $this->permissionProvider    = $permissionProvider;
        $this->userHelper            = $userHelper;
    }

    public function setMetadata(CustomField $entity): CustomField
    {
        $user = $this->userHelper->getUser();
        $now  = new DateTimeHelper();

        if ($entity->isNew()) {
            $entity->setCreatedBy($user);
            $entity->setCreatedByUser($user->getName());
            $entity->setDateAdded($now->getUtcDateTime());
            $this->setAlias($entity);
        }

        $entity->setModifiedBy($user);
        $entity->setModifiedByUser($user->getName());
        $entity->setDateModified($now->getUtcDateTime());

        return $entity;
    }

    public function setAlias(CustomField $entity): CustomField
    {
        $entity = $this->sanitizeAlias($entity);

        return $this->ensureUniqueAlias($entity);
    }

    /**
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomField
    {
        /** @var CustomField|null $customField */
        $customField = parent::getEntity($id);

        if (null === $customField) {
            throw new NotFoundException("Custom Field with ID = {$id} was not found");
        }

        return $customField;
    }

    /**
     * @return CustomField[]
     */
    public function fetchCustomFieldsForObject(CustomObject $customObject): array
    {
        return $this->fetchEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'e.customObject',
                        'value'  => $customObject->getId(),
                        'expr'   => 'eq',
                    ],
                    [
                        'column' => 'e.isPublished',
                        'value'  => true,
                        'expr'   => 'eq',
                    ],
                ],
            ],
            'orderBy'          => 'e.order',
            'orderByDir'       => 'ASC',
            'ignore_paginator' => true,
        ]);
    }

    /**
     * @param mixed[] $args
     *
     * @return Paginator|CustomField[]
     */
    public function fetchEntities(array $args = [])
    {
        return parent::getEntities($this->addCreatorLimit($args));
    }

    /**
     * Used only by Mautic's generic methods. Use DI instead.
     */
    public function getRepository(): CommonRepository
    {
        return $this->customFieldRepository;
    }

    /**
     * Used only by Mautic's generic methods. Use CustomFieldPermissionProvider instead.
     */
    public function getPermissionBase(): string
    {
        return 'custom_fields:custom_fields';
    }

    /**
     **.
     */
    private function sanitizeAlias(CustomField $entity): CustomField
    {
        $dirtyAlias = $entity->getAlias();
        if (empty($dirtyAlias)) {
            $dirtyAlias = $entity->getName();
        }
        $cleanAlias = $this->cleanAlias($dirtyAlias, '', 0, '-');
        $entity->setAlias($cleanAlias);

        return $entity;
    }

    /**
     * Make sure alias is not already taken.
     */
    private function ensureUniqueAlias(CustomField $entity): CustomField
    {
        $testAlias = $entity->getAlias();
        $isUnique  = $this->customFieldRepository->isAliasUnique($testAlias, $entity->getId());
        $counter   = 1;
        while ($isUnique) {
            $testAlias .= $counter;
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
     * @param mixed[] $args
     *
     * @return mixed[]
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

            $args['filter']['force'] += $limitOwnerFilter;
        }

        return $args;
    }
}
