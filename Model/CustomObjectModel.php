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

use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Mautic\CoreBundle\Helper\UserHelper;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomObjectPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\CustomObjectEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomObjectEvent;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;

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
     * @param EventDispatcherInterface       $dispatcher
     */
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

    /**
     * @param CustomObject $customObject
     *
     * @return CustomObject
     */
    public function save(CustomObject $customObject): CustomObject
    {
        $user  = $this->userHelper->getUser();
        $now   = new DateTimeHelper();
        $event = new CustomObjectEvent($customObject, $customObject->isNew());

        if ($customObject->isNew()) {
            $customObject->setCreatedBy($user->getId());
            $customObject->setCreatedByUser($user->getName());
            $customObject->setDateAdded($now->getUtcDateTime());
        }

        $customObject->setModifiedBy($user->getId());
        $customObject->setModifiedByUser($user->getName());
        $customObject->setDateModified($now->getUtcDateTime());

        $customObject->recordCustomFieldChanges();

        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_SAVE, $event);

        $this->entityManager->persist($customObject);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_POST_SAVE, $event);

        return $customObject;
    }

    /**
     * @param CustomObject $customObject
     */
    public function delete(CustomObject $customObject): void
    {
        //take note of ID before doctrine wipes it out
        $id    = $customObject->getId();
        $event = new CustomObjectEvent($customObject);
        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_PRE_DELETE, $event);

        $this->entityManager->remove($customObject);
        $this->entityManager->flush();

        //set the id for use in events
        $customObject->deletedId = $id;
        $this->dispatcher->dispatch(CustomObjectEvents::ON_CUSTOM_OBJECT_POST_DELETE, $event);
    }

    /**
     * @param int $id
     *
     * @return CustomObject
     *
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomObject
    {
        $customObject = parent::getEntity($id);

        if (null === $customObject) {
            throw new NotFoundException("Custom Object with ID = {$id} was not found");
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
                        'column' => CustomObjectRepository::TABLE_ALIAS.'.isPublished',
                        'value'  => true,
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return CustomObject[]
     */
    public function getTableData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->customObjectRepository->getTableDataQuery($tableConfig);
        $queryBuilder = $this->applyOwnerFilter($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return int
     */
    public function getCountForTable(TableConfig $tableConfig): int
    {
        $queryBuilder = $this->customObjectRepository->getTableCountQuery($tableConfig);
        $queryBuilder = $this->applyOwnerFilter($queryBuilder);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param CustomObject $customObject
     * @param int          $customFieldId
     */
    public function removeCustomFieldById(CustomObject $customObject, int $customFieldId): void
    {
        foreach ($customObject->getCustomFields() as $customField) {
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
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @param CustomObject       $customObject
     *
     * @return mixed[]
     */
    public function getItemsLineChartData(\DateTimeInterface $from, \DateTimeInterface $to, CustomObject $customObject): array
    {
        $chart = new LineChart(null, $from, $to);
        $query = new ChartQuery($this->entityManager->getConnection(), $from, $to);
        $items = $query->fetchTimeData('custom_item', 'date_added', ['custom_object_id' => $customObject->getId()]);
        $chart->setDataset($this->translator->trans('custom.object.created.items'), $items);

        return $chart->render();
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
                    'column' => CustomObjectRepository::TABLE_ALIAS.'.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->userHelper->getUser()->getId(),
                ],
            ];

            $args['filter']['force'] += $limitOwnerFilter;
        }

        return $args;
    }

    /**
     * Adds condition for owner if the user doesn't have permissions to view other.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    private function applyOwnerFilter(QueryBuilder $queryBuilder): QueryBuilder
    {
        try {
            $this->permissionProvider->isGranted('viewother');
        } catch (ForbiddenException $e) {
            $this->customObjectRepository->applyOwnerId(
                $queryBuilder,
                $this->userHelper->getUser()->getId()
            );
        }

        return $queryBuilder;
    }
}
