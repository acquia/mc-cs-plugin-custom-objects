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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\InUseException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    public function __construct(
        EntityManager $entityManager,
        CustomObjectRepository $customObjectRepository,
        CustomObjectPermissionProvider $permissionProvider,
        UserHelper $userHelper,
        CustomFieldModel $customFieldModel,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager          = $entityManager;
        $this->customObjectRepository = $customObjectRepository;
        $this->permissionProvider     = $permissionProvider;
        $this->userHelper             = $userHelper;
        $this->customFieldModel       = $customFieldModel;
        $this->dispatcher             = $dispatcher;
    }

    public function save(CustomObject $customObject): CustomObject
    {
        $user         = $this->userHelper->getUser();
        $customObject = $this->sanitizeAlias($customObject);
        $customObject = $this->ensureUniqueAlias($customObject);
        $now          = new DateTimeHelper();
        $event        = new CustomObjectEvent($customObject, $customObject->isNew());

        if ($customObject->isNew()) {
            $customObject->setCreatedBy($user);
            $customObject->setCreatedByUser($user->getName());
            $customObject->setDateAdded($now->getUtcDateTime());
        }

        $customObject->setModifiedBy($user);
        $customObject->setModifiedByUser($user->getName());
        $customObject->setDateModified($now->getUtcDateTime());

        $customObject->recordCustomFieldChanges();

        $this->setCustomFieldsMetadata($customObject);

        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_SAVE, $event);

        $this->entityManager->persist($customObject);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_POST_SAVE, $event);

        return $customObject;
    }

    public function delete(CustomObject $customObject): void
    {
        // Take note of ID before doctrine wipes it out
        $id    = $customObject->getId();
        $event = new CustomObjectEvent($customObject);
        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_DELETE, $event);

        $this->entityManager->remove($customObject);
        $this->entityManager->flush();

        // Set the id for use in events
        $customObject->deletedId = $id;
        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_POST_DELETE, $event);
    }

    /**
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomObject
    {
        /** @var CustomObject|null */
        $customObject = parent::getEntity($id);

        if (null === $customObject) {
            throw new NotFoundException("Custom Object with ID = {$id} was not found");
        }

        $customObject->createFieldsSnapshot();

        return $customObject;
    }

    /**
     * @throws InUseException
     */
    public function checkCustomObjectIsAssociated(int $customObjectId): Void
    {   
        
        $fieldValueLength = 4 + strlen((string)$customObjectId); // 4 char for cmo_
        
        $queryBuilder = $this->em->getConnection()->createQueryBuilder();
        $queryBuilder->select('l.name')
        ->from(MAUTIC_TABLE_PREFIX.'lead_lists','l')
        ->where($queryBuilder->expr()->eq('l.is_published', 1))
        ->andWhere($queryBuilder->expr()->like('l.filters', ':object'))
        ->andWhere($queryBuilder->expr()->like('l.filters', ':field'))
        ->setParameter('object', '%s:6:"object";s:13:"custom_object";%')
        ->setParameter('field', '%s:5:"field";s:'.$fieldValueLength.':"cmo_'.$customObjectId.'";%');
        
        $segmentList = $queryBuilder->execute()->fetchAll();
        if (!empty($segmentList))
        {
            $list = array_column($segmentList, 'name');
            $exception = new InUseException();
            $exception->setSegmentList($list);
            throw $exception;
        }
    }
    
    /**
     * @throws NotFoundException
     */
    public function fetchEntityByAlias(string $alias): CustomObject
    {
        /** @var CustomObject|null */
        $customObject = $this->customObjectRepository->findOneBy(['alias' => $alias]);

        if (null === $customObject) {
            throw new NotFoundException("Custom Object with alias = {$alias} was not found");
        }

        $customObject->createFieldsSnapshot();

        return $customObject;
    }

    /**
     * @param mixed[] $args
     *
     * @return Paginator|CustomObject[]
     */
    public function fetchEntities(array $args = [])
    {
        return parent::getEntities($this->addCreatorLimit($args));
    }

    /**
     * @return CustomObject[]
     */
    public function fetchAllPublishedEntities(): array
    {
        return $this->fetchEntities([
            'ignore_paginator' => true,
            'filter'           => [
                'force' => [
                    [
                        'column' => CustomObject::TABLE_ALIAS.'.isPublished',
                        'value'  => true,
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return CustomObject[]
     */
    public function getTableData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->createListQueryBuilder($tableConfig);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getCountForTable(TableConfig $tableConfig): int
    {
        $queryBuilder = $this->createListQueryBuilder($tableConfig);
        $queryBuilder->select($queryBuilder->expr()->countDistinct(CustomObject::TABLE_ALIAS));
        $queryBuilder->setMaxResults(1);
        $queryBuilder->setFirstResult(0);
        $queryBuilder->resetDQLPart('orderBy');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function removeCustomFieldById(CustomObject $customObject, int $customFieldId): void
    {
        foreach ($customObject->getCustomFields() as $customField) {
            if ($customField->getId() === $customFieldId) {
                $customObject->removeCustomField($customField);
                if ($this->entityManager->contains($customField)) {
                    // We need to ensure that field exists when cloning CO with deleted fields
                    $this->customFieldModel->deleteEntity($customField);
                }
            }
        }
    }

    /**
     * Used only by Mautic's generic methods. Use DI instead.
     */
    public function getRepository(): CommonRepository
    {
        return $this->customObjectRepository;
    }

    /**
     * Used only by Mautic's generic methods. Use CustomFieldPermissionProvider instead.
     */
    public function getPermissionBase(): string
    {
        return 'custom_objects:custom_objects';
    }

    /**
     * @return mixed[]
     */
    public function getItemsLineChartData(\DateTime $from, \DateTime $to, CustomObject $customObject): array
    {
        $chart = new LineChart(null, $from, $to);
        $query = new ChartQuery($this->entityManager->getConnection(), $from, $to);
        $items = $query->fetchTimeData('custom_item', 'date_added', ['custom_object_id' => $customObject->getId()]);
        $chart->setDataset($this->translator->trans('custom.object.created.items'), $items);

        return $chart->render();
    }

    private function createListQueryBuilder(TableConfig $tableConfig): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder = $tableConfig->configureOrmQueryBuilder($queryBuilder);
        $queryBuilder->select(CustomObject::TABLE_ALIAS);
        $queryBuilder->from(CustomObject::class, CustomObject::TABLE_ALIAS);

        $search = $tableConfig->getParameter('search');

        if ($search) {
            $queryBuilder->andWhere(CustomObject::TABLE_ALIAS.'.name LIKE %:search%');
            $queryBuilder->setParameter('search', $search);
        }

        return $this->applyOwnerFilter($queryBuilder);
    }

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
     */
    private function ensureUniqueAlias(CustomObject $entity): CustomObject
    {
        $testAlias   = $entity->getAlias();
        $aliasExists = $this->customObjectRepository->checkAliasExists($testAlias, $entity->getId());
        $counter     = 1;
        while ($aliasExists) {
            $testAlias .= $counter;
            $aliasExists = $this->customObjectRepository->checkAliasExists($testAlias, $entity->getId());
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
        // We don't know the user when executed through CLI.
        if (!$this->userHelper->getUser() || !$this->userHelper->getUser()->getId()) {
            return $args;
        }

        try {
            $this->permissionProvider->isGranted('viewother');
        } catch (ForbiddenException $e) {
            if (!isset($args['filter'])) {
                $args['filter'] = [];
            }

            if (!isset($args['filter']['force'])) {
                $args['filter']['force'] = [];
            }

            $args['filter']['force'][] = [
                'column' => CustomObject::TABLE_ALIAS.'.createdBy',
                'expr'   => 'eq',
                'value'  => $this->userHelper->getUser()->getId(),
            ];
        }

        return $args;
    }

    /**
     * Adds condition for owner if the user doesn't have permissions to view other.
     */
    private function applyOwnerFilter(QueryBuilder $queryBuilder): QueryBuilder
    {
        try {
            $this->permissionProvider->isGranted('viewother');
        } catch (ForbiddenException $e) {
            $queryBuilder->andWhere(CustomObject::TABLE_ALIAS.'.createdBy', $this->userHelper->getUser()->getId());
        }

        return $queryBuilder;
    }

    private function setCustomFieldsMetadata(CustomObject $customObject): CustomObject
    {
        foreach ($customObject->getCustomFields() as $customField) {
            $this->customFieldModel->setMetadata($customField);
        }

        return $customObject;
    }
}
