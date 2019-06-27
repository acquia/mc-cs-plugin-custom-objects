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

use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use UnexpectedValueException;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;

class CustomItemModel extends FormModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomItemRepository
     */
    private $customItemRepository;

    /**
     * @var CustomItemPermissionProvider
     */
    private $permissionProvider;

    /**
     * @var CustomFieldValueModel
     */
    private $customFieldValueModel;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param EntityManager                $entityManager
     * @param CustomItemRepository         $customItemRepository
     * @param CustomItemPermissionProvider $permissionProvider
     * @param UserHelper                   $userHelper
     * @param CustomFieldValueModel        $customFieldValueModel
     * @param EventDispatcherInterface     $dispatcher
     * @param ValidatorInterface           $validator
     */
    public function __construct(
        EntityManager $entityManager,
        CustomItemRepository $customItemRepository,
        CustomItemPermissionProvider $permissionProvider,
        UserHelper $userHelper,
        CustomFieldValueModel $customFieldValueModel,
        EventDispatcherInterface $dispatcher,
        ValidatorInterface $validator
    ) {
        $this->entityManager         = $entityManager;
        $this->customItemRepository  = $customItemRepository;
        $this->permissionProvider    = $permissionProvider;
        $this->userHelper            = $userHelper;
        $this->customFieldValueModel = $customFieldValueModel;
        $this->dispatcher            = $dispatcher;
        $this->validator             = $validator;
    }

    /**
     * @param CustomItem $customItem
     * @param bool       $dryRun
     *
     * @return CustomItem
     */
    public function save(CustomItem $customItem, bool $dryRun = false): CustomItem
    {
        $user  = $this->userHelper->getUser();
        $now   = new DateTimeHelper();
        $event = new CustomItemEvent($customItem, $customItem->isNew());

        if ($customItem->isNew()) {
            $customItem->setCreatedBy($user);
            $customItem->setCreatedByUser($user->getName());
            $customItem->setDateAdded($now->getUtcDateTime());
        }

        $customItem->setModifiedBy($user);
        $customItem->setModifiedByUser($user->getName());
        $customItem->setDateModified($now->getUtcDateTime());

        if (!$dryRun) {
            $this->entityManager->persist($customItem);
        }

        $customItem->getCustomFieldValues()->map(function (CustomFieldValueInterface $customFieldValue) use ($dryRun): void {
            $this->customFieldValueModel->save($customFieldValue, $dryRun);
        });

        $errors = $this->validator->validate($customItem);

        if ($errors->count() > 0) {
            throw new InvalidValueException($errors->get(0)->getMessage());
        }

        $customItem->recordCustomFieldValueChanges();

        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $event);

        if (!$dryRun) {
            $this->entityManager->flush($customItem);
            $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $event);
        }

        return $customItem;
    }

    /**
     * @param CustomItem $customItem
     * @param string     $entityType
     * @param int        $entityId
     *
     * @return CustomItemXrefInterface
     *
     * @throws UnexpectedValueException
     */
    public function linkEntity(CustomItem $customItem, string $entityType, int $entityId): CustomItemXrefInterface
    {
        $event = new CustomItemXrefEntityDiscoveryEvent($customItem, $entityType, $entityId);

        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY, $event);

        if (!$event->getXrefEntity() instanceof CustomItemXrefInterface) {
            throw new UnexpectedValueException("Entity {$entityType} was not able to be linked to {$customItem->getName()} ({$customItem->getId()})");
        }

        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY, new CustomItemXrefEntityEvent($event->getXrefEntity()));

        return $event->getXrefEntity();
    }

    /**
     * @param CustomItem $customItem
     * @param string     $entityType
     * @param int        $entityId
     *
     * @return CustomItemXrefInterface
     *
     * @throws UnexpectedValueException
     */
    public function unlinkEntity(CustomItem $customItem, string $entityType, int $entityId): CustomItemXrefInterface
    {
        $event = new CustomItemXrefEntityDiscoveryEvent($customItem, $entityType, $entityId);

        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY, $event);

        if (!$event->getXrefEntity() instanceof CustomItemXrefInterface) {
            throw new UnexpectedValueException("Entity {$entityType} was not able to be unlinked from {$customItem->getName()} ({$customItem->getId()})");
        }

        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_ENTITY, new CustomItemXrefEntityEvent($event->getXrefEntity()));

        return $event->getXrefEntity();
    }

    /**
     * @param CustomItem $customItem
     */
    public function delete(CustomItem $customItem): void
    {
        //take note of ID before doctrine wipes it out
        $id    = $customItem->getId();
        $event = new CustomItemEvent($customItem);
        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_PRE_DELETE, $event);

        $this->entityManager->remove($customItem);
        $this->entityManager->flush();

        //set the id for use in events
        $customItem->deletedId = $id;
        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE, $event);
    }

    /**
     * @param int $id
     *
     * @return CustomItem
     *
     * @throws NotFoundException
     */
    public function fetchEntity(int $id): CustomItem
    {
        /** @var CustomItem|null $customItem */
        $customItem = parent::getEntity($id);

        if (null === $customItem) {
            throw new NotFoundException("Custom Item with ID = {$id} was not found");
        }

        return $this->populateCustomFields($customItem);
    }

    /**
     * Returns a list of entities (ORM).
     * 
     * @param TableConfig $tableConfig
     *
     * @return CustomItem[]
     */
    public function getTableData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->createListOrmQueryBuilder($tableConfig);

        $this->dispatcher->dispatch(
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_ORM_QUERY,
            new CustomItemListQueryEvent($queryBuilder, $tableConfig)
        );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Returns a list of arrays representing custom items (DBAL).
     * 
     * @param TableConfig $tableConfig
     *
     * @return CustomItem[]
     */
    public function getArrayTableData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->createListDbalQueryBuilder($tableConfig);

        $this->dispatcher->dispatch(
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_DBAL_QUERY,
            new CustomItemListDbalQueryEvent($queryBuilder, $tableConfig)
        );

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return int
     */
    public function getCountForTable(TableConfig $tableConfig): int
    {
        $queryBuilder = $this->createListOrmQueryBuilder($tableConfig);
        $queryBuilder->select($queryBuilder->expr()->countDistinct(CustomItem::TABLE_ALIAS));

        $this->dispatcher->dispatch(
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_ORM_QUERY,
            new CustomItemListQueryEvent($queryBuilder, $tableConfig)
        );

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return mixed[]
     */
    public function getLookupData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->createListOrmQueryBuilder($tableConfig);
        $rootAlias    = CustomItem::TABLE_ALIAS;
        $queryBuilder->select("{$rootAlias}.name as value, {$rootAlias}.id");

        $this->dispatcher->dispatch(
            CustomItemEvents::ON_CUSTOM_ITEM_LOOKUP_QUERY,
            new CustomItemListQueryEvent($queryBuilder, $tableConfig)
        );

        $rows = $queryBuilder->getQuery()->getArrayResult();
        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id'    => $row['id'],
                'value' => "{$row['value']} ({$row['id']})",
            ];
        }

        return $data;
    }

    /**
     * @param CustomItem $customItem
     *
     * @return CustomItem
     */
    public function populateCustomFields(CustomItem $customItem): CustomItem
    {
        if ($customItem->getCustomFieldValues()->count() > 0) {
            // Field values are present already.
            return $customItem;
        }

        $this->customFieldValueModel->createValuesForItem($customItem);

        $customItem->createFieldValuesSnapshot();

        return $customItem;
    }

    /**
     * Used only by Mautic's generic methods. Use DI instead.
     *
     * @return CommonRepository
     */
    public function getRepository(): CommonRepository
    {
        return $this->customItemRepository;
    }

    /**
     * Used only by Mautic's generic methods. Use CustomItemPermissionProvider instead.
     *
     * 'custom_objects:custom_objects' is used as custom item permissions are dynamic
     * and must contain the specific custom object ID.
     *
     * @return string
     */
    public function getPermissionBase(): string
    {
        return 'custom_objects:custom_objects';
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return QueryBuilder
     *
     * @throws UnexpectedValueException
     */
    private function createListOrmQueryBuilder(TableConfig $tableConfig): QueryBuilder
    {
        $customObjectId = $tableConfig->getParameter('customObjectId');
        $search         = $tableConfig->getParameter('search');
        $queryBuilder   = $this->entityManager->createQueryBuilder();
        $queryBuilder   = $tableConfig->configureOrmQueryBuilder($queryBuilder);

        if (empty($customObjectId)) {
            throw new UnexpectedValueException("customObjectId cannot be empty. It's required for permission management");
        }

        $queryBuilder->select(CustomItem::TABLE_ALIAS);
        $queryBuilder->from(CustomItem::class, CustomItem::TABLE_ALIAS);
        $queryBuilder->where(CustomItem::TABLE_ALIAS.'.customObject = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObjectId);

        if ($search) {
            $queryBuilder->andWhere(CustomItem::TABLE_ALIAS.'.name LIKE :search');
            $queryBuilder->setParameter('search', "%{$search}%");
        }

        return $this->applyOwnerFilter($queryBuilder, $customObjectId);
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return DbalQueryBuilder
     *
     * @throws UnexpectedValueException
     */
    private function createListDbalQueryBuilder(TableConfig $tableConfig): DbalQueryBuilder
    {
        $customObjectId = $tableConfig->getParameter('customObjectId');
        $search         = $tableConfig->getParameter('search');
        $queryBuilder   = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder   = $tableConfig->configureDbalQueryBuilder($queryBuilder);

        if (empty($customObjectId)) {
            throw new UnexpectedValueException("customObjectId cannot be empty. It's required for permission management");
        }

        $queryBuilder->select(CustomItem::TABLE_ALIAS.'.*');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.CustomItem::TABLE_NAME, CustomItem::TABLE_ALIAS);
        $queryBuilder->where(CustomItem::TABLE_ALIAS.'.custom_object_id = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObjectId);

        if ($search) {
            $queryBuilder->andWhere(CustomItem::TABLE_ALIAS.'.name LIKE :search');
            $queryBuilder->setParameter('search', "%{$search}%");
        }

        return $this->applyOwnerFilter($queryBuilder, $customObjectId);
    }

    /**
     * Adds condition for owner if the user doesn't have permissions to view other.
     *
     * @param QueryBuilder|DbalQueryBuilder $queryBuilder
     *
     * @return QueryBuilder|DbalQueryBuilder
     */
    private function applyOwnerFilter($queryBuilder, int $customObjectId)
    {
        $user = $this->userHelper->getUser();

        if (null === $user || !$user->getId()) {
            // The code is run from CLI.
            return $queryBuilder;
        }

        try {
            $this->permissionProvider->isGranted('viewother', $customObjectId);
        } catch (ForbiddenException $e) {
            $queryBuilder->andWhere(CustomItem::TABLE_ALIAS.'.createdBy', $user->getId());
        }

        return $queryBuilder;
    }
}
