<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Doctrine\Helper\FulltextKeyword;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\DTO\CustomItemFieldListData;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueOption;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListDbalQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemListQueryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityDiscoveryEvent;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemXrefEntityEvent;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidValueException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnexpectedValueException;

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

    public function save(CustomItem $customItem, bool $dryRun = false): CustomItem
    {
        $user = $this->userHelper->getUser();
        $now  = new DateTimeHelper();

        if ($customItem->isNew()) {
            $customItem->setCreatedBy($user);
            $customItem->setCreatedByUser($user->getName());
            $customItem->setDateAdded($now->getUtcDateTime());
        }

        $customItem->setModifiedBy($user);
        $customItem->setModifiedByUser($user->getName());
        $customItem->setDateModified($now->getUtcDateTime());

        $errors = $this->validator->validate($customItem);
        $customItem->updateUniqueHash();

        if ($errors->count() > 0) {
            throw new InvalidValueException($errors->get(0)->getMessage());
        }

        $this->dispatcher->dispatch(new CustomItemEvent($customItem, $customItem->isNew(), CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE));

        if (!$dryRun) {
            if ($customItem->isNew()) {
                // Custom item is new so we need to upsert it to atomically find whether it exists based on unique fields or not.
                $this->entityManager->detach($customItem);
                $this->customItemRepository->upsert($customItem);

                // We need to re-attach the entity to the entity manager so that it can be saved by the rest of the code.
                $customFieldValues = $customItem->getCustomFieldValues();
                $hasBeenUpdated = $customItem->hasBeenUpdated();
                $hasBeenInserted = $customItem->hasBeenInserted();
                $customItem        = $this->fetchEntity($customItem->getId());

                $customItem->setHasBeenUpdated($hasBeenUpdated);
                $customItem->setHasBeenInserted($hasBeenInserted);

                foreach ($customFieldValues as $customFieldValue) {
                    $customFieldValue->setCustomItem($customItem);
                    $customItem->addCustomFieldValue($customFieldValue);
                }
            } else {
                $this->entityManager->persist($customItem);
                $this->entityManager->flush();
            }

            $customItem->getCustomFieldValues()->map(
                fn (CustomFieldValueInterface $customFieldValue) => $this->customFieldValueModel->save($customFieldValue, $dryRun)
            );

            $customItem->recordCustomFieldValueChanges();

            $this->dispatcher->dispatch(new CustomItemEvent($customItem, $customItem->isNew(), CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE));
        }

        return $customItem;
    }

    /**
     * @param object $entity
     * @param null   $extra
     *
     * @throws InvalidValueException
     */
    public function unlockEntity($entity, $extra = null): void
    {
        if ($entity->getId()) {
            $entity->setCheckedOut(null);
            $entity->setCheckedOutBy(null);

            $this->save($entity);
        }
    }

    /**
     * @throws UnexpectedValueException
     */
    public function linkEntity(CustomItem $customItem, string $entityType, int $entityId): CustomItemXrefInterface
    {
        $event = new CustomItemXrefEntityDiscoveryEvent($customItem, $entityType, $entityId);

        $this->dispatcher->dispatch($event, CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY);

        if (!$event->getXrefEntity() instanceof CustomItemXrefInterface) {
            throw new UnexpectedValueException("Entity {$entityType} was not able to be linked to {$customItem->getName()} ({$customItem->getId()})");
        }

        $this->dispatcher->dispatch(new CustomItemXrefEntityEvent($event->getXrefEntity(), CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY));

        return $event->getXrefEntity();
    }

    /**
     * @throws UnexpectedValueException
     */
    public function unlinkEntity(CustomItem $customItem, string $entityType, int $entityId): CustomItemXrefInterface
    {
        $event = new CustomItemXrefEntityDiscoveryEvent($customItem, $entityType, $entityId);

        $this->dispatcher->dispatch($event, CustomItemEvents::ON_CUSTOM_ITEM_LINK_ENTITY_DISCOVERY);

        if (!$event->getXrefEntity() instanceof CustomItemXrefInterface) {
            throw new UnexpectedValueException("Entity {$entityType} was not able to be unlinked from {$customItem->getName()} ({$customItem->getId()})");
        }

        $this->dispatcher->dispatch(new CustomItemXrefEntityEvent($event->getXrefEntity(), CustomItemEvents::ON_CUSTOM_ITEM_UNLINK_ENTITY));

        return $event->getXrefEntity();
    }

    public function delete(CustomItem $customItem): void
    {
        //take note of ID before doctrine wipes it out
        $id    = $customItem->getId();
        $event = new CustomItemEvent($customItem);
        $this->dispatcher->dispatch($event, CustomItemEvents::ON_CUSTOM_ITEM_PRE_DELETE);

        $this->entityManager->remove($customItem);
        $this->entityManager->flush();

        //set the id for use in events
        $customItem->deletedId = $id;
        $this->dispatcher->dispatch($event, CustomItemEvents::ON_CUSTOM_ITEM_POST_DELETE);
    }

    /**
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
     * @return CustomItem[]
     */
    public function getTableData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->createListOrmQueryBuilder($tableConfig);

        $this->dispatcher->dispatch(
            new CustomItemListQueryEvent($queryBuilder, $tableConfig),
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_ORM_QUERY
         );

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Returns a list of arrays representing custom items (DBAL).
     */
    public function getArrayTableData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->createListDbalQueryBuilder($tableConfig);

        $this->dispatcher->dispatch(
            new CustomItemListDbalQueryEvent($queryBuilder, $tableConfig),
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_DBAL_QUERY
        );

        return $queryBuilder->execute()->fetchAll();
    }

    public function getCountForTable(TableConfig $tableConfig): int
    {
        $queryBuilder = $this->createListOrmQueryBuilder($tableConfig);
        $queryBuilder->select($queryBuilder->expr()->countDistinct(CustomItem::TABLE_ALIAS));
        $queryBuilder->setMaxResults(1);
        $queryBuilder->setFirstResult(0);
        $queryBuilder->resetDQLPart('orderBy');

        $this->dispatcher->dispatch(
            new CustomItemListQueryEvent($queryBuilder, $tableConfig),
            CustomItemEvents::ON_CUSTOM_ITEM_LIST_ORM_QUERY
        );

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return mixed[]
     */
    public function getLookupData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->createListOrmQueryBuilder($tableConfig);
        $rootAlias    = CustomItem::TABLE_ALIAS;
        $queryBuilder->select("{$rootAlias}.name as value, {$rootAlias}.id");

        $this->dispatcher->dispatch(
            new CustomItemListQueryEvent($queryBuilder, $tableConfig),
            CustomItemEvents::ON_CUSTOM_ITEM_LOOKUP_QUERY
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
     * @param CustomItem[] $customItems
     */
    public function getFieldListData(CustomObject $customObject, array $customItems, string $filterEntityType): ?CustomItemFieldListData
    {
        switch ($filterEntityType) {
            case 'customItem':
                $customFields = $customObject->getFieldsShowInCustomObjectDetailList();
                break;
            case 'contact':
                $customFields = $customObject->getFieldsShowInContactDetailList();
                break;
            default:
                $customFields = $customObject->getPublishedFields();
                break;
        }

        return $this->customFieldValueModel->getItemsListData($customFields, $customItems);
    }

    /**
     * Used only by Mautic's generic methods. Use DI instead.
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
     */
    public function getPermissionBase(): string
    {
        return 'custom_objects:custom_objects';
    }

    /**
     * @throws UnexpectedValueException
     */
    private function createListOrmQueryBuilder(TableConfig $tableConfig): QueryBuilder
    {
        $this->validateTableConfig($tableConfig);

        $customObjectId = $tableConfig->getParameter('customObjectId');
        $search         = $tableConfig->getParameter('search');
        $queryBuilder   = $this->entityManager->createQueryBuilder();
        $queryBuilder   = $tableConfig->configureOrmQueryBuilder($queryBuilder);

        $queryBuilder->select(CustomItem::TABLE_ALIAS);
        $queryBuilder->from(CustomItem::class, CustomItem::TABLE_ALIAS);
        $queryBuilder->where(CustomItem::TABLE_ALIAS.'.customObject = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObjectId);

        if ($search) {
            $this->applySearchFilter($queryBuilder, $search);
        }

        return $this->applyOwnerFilter($queryBuilder, $customObjectId);
    }

    /**
     * @throws UnexpectedValueException
     */
    private function createListDbalQueryBuilder(TableConfig $tableConfig): DbalQueryBuilder
    {
        $this->validateTableConfig($tableConfig);

        $customObjectId = $tableConfig->getParameter('customObjectId');
        $queryBuilder   = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder   = $tableConfig->configureDbalQueryBuilder($queryBuilder);

        $queryBuilder->select(CustomItem::TABLE_ALIAS.'.*');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.CustomItem::TABLE_NAME, CustomItem::TABLE_ALIAS);
        $queryBuilder->where(CustomItem::TABLE_ALIAS.'.custom_object_id = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObjectId);

        return $this->applyOwnerFilter($queryBuilder, $customObjectId);
    }

    /**
     * @throws UnexpectedValueException
     */
    private function validateTableConfig(TableConfig $tableConfig): void
    {
        if (empty($tableConfig->getParameter('customObjectId'))) {
            throw new UnexpectedValueException("customObjectId cannot be empty. It's required for permission management");
        }
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

    private function applySearchFilter(QueryBuilder $queryBuilder, string $search): void
    {
        $valueTextBuilder = $this->entityManager->createQueryBuilder();
        $valueTextBuilder->select('IDENTITY(ValueText.customItem)');
        $valueTextBuilder->from(CustomFieldValueText::class, 'ValueText');
        $valueTextBuilder->andWhere('MATCH (ValueText.value) AGAINST (:search BOOLEAN) > 0');

        $valueOptionBuilder = $this->entityManager->createQueryBuilder();
        $valueOptionBuilder->select('IDENTITY(ValueOption.customItem)');
        $valueOptionBuilder->from(CustomFieldValueOption::class, 'ValueOption');
        $valueOptionBuilder->andWhere('MATCH (ValueOption.value) AGAINST (:search BOOLEAN) > 0');

        $exprBuilder = $queryBuilder->expr();
        $orCondition = $exprBuilder->orX();
        $orCondition->add('MATCH ('.CustomItem::TABLE_ALIAS.'.name) AGAINST (:search BOOLEAN) > 0');
        $orCondition->add($exprBuilder->in(CustomItem::TABLE_ALIAS.'.id', $valueTextBuilder->getDQL()));
        $orCondition->add($exprBuilder->in(CustomItem::TABLE_ALIAS.'.id', $valueOptionBuilder->getDQL()));

        $queryBuilder->andWhere($orCondition);
        $queryBuilder->setParameter('search', (string) new FulltextKeyword($search));
    }
}
