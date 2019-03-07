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
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\CustomObjectsBundle\CustomItemEvents;
use MauticPlugin\CustomObjectsBundle\Event\CustomItemEvent;

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
     * @var CustomFieldModel
     */
    private $customFieldModel;

    /**
     * @var CustomFieldValueModel
     */
    private $customFieldValueModel;

    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @param EntityManager                $entityManager
     * @param CustomItemRepository         $customItemRepository
     * @param CustomItemPermissionProvider $permissionProvider
     * @param UserHelper                   $userHelper
     * @param CustomFieldModel             $customFieldModel
     * @param CustomFieldValueModel        $customFieldValueModel
     * @param CustomFieldTypeProvider      $customFieldTypeProvider
     * @param EventDispatcherInterface     $dispatcher
     */
    public function __construct(
        EntityManager $entityManager,
        CustomItemRepository $customItemRepository,
        CustomItemPermissionProvider $permissionProvider,
        UserHelper $userHelper,
        CustomFieldModel $customFieldModel,
        CustomFieldValueModel $customFieldValueModel,
        CustomFieldTypeProvider $customFieldTypeProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager           = $entityManager;
        $this->customItemRepository    = $customItemRepository;
        $this->permissionProvider      = $permissionProvider;
        $this->userHelper              = $userHelper;
        $this->customFieldModel        = $customFieldModel;
        $this->customFieldValueModel   = $customFieldValueModel;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
        $this->dispatcher              = $dispatcher;
    }

    /**
     * @param CustomItem $customItem
     *
     * @return CustomItem
     */
    public function save(CustomItem $customItem): CustomItem
    {
        $user  = $this->userHelper->getUser();
        $now   = new DateTimeHelper();
        $event = new CustomItemEvent($customItem, $customItem->isNew());

        if ($customItem->isNew()) {
            $customItem->setCreatedBy($user->getId());
            $customItem->setCreatedByUser($user->getName());
            $customItem->setDateAdded($now->getUtcDateTime());
        }

        $customItem->setModifiedBy($user->getId());
        $customItem->setModifiedByUser($user->getName());
        $customItem->setDateModified($now->getUtcDateTime());

        $this->entityManager->persist($customItem);

        foreach ($customItem->getCustomFieldValues() as $customFieldValue) {
            $this->customFieldValueModel->save($customFieldValue);
        }

        foreach ($customItem->getContactReferences() as $reference) {
            $this->entityManager->persist($reference);
        }

        $customItem->recordCustomFieldValueChanges();

        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_PRE_SAVE, $event);
        $this->entityManager->flush();
        $this->dispatcher->dispatch(CustomItemEvents::ON_CUSTOM_ITEM_POST_SAVE, $event);

        return $customItem;
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
        $entity = parent::getEntity($id);

        if (null === $entity) {
            throw new NotFoundException("Custom Item with ID = {$id} was not found");
        }

        return $this->populateCustomFields($entity);
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return CustomItem[]
     */
    public function getTableData(TableConfig $tableConfig): array
    {
        $customObjectFilter = $tableConfig->getFilter(CustomItem::class, 'customObject');
        $queryBuilder       = $this->customItemRepository->getTableDataQuery($tableConfig);
        $queryBuilder       = $this->applyOwnerFilter($queryBuilder, $customObjectFilter->getValue());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return int
     */
    public function getCountForTable(TableConfig $tableConfig): int
    {
        $customObjectFilter = $tableConfig->getFilter(CustomItem::class, 'customObject');
        $queryBuilder       = $this->customItemRepository->getTableCountQuery($tableConfig);
        $queryBuilder       = $this->applyOwnerFilter($queryBuilder, $customObjectFilter->getValue());

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param TableConfig $tableConfig
     *
     * @return mixed[]
     */
    public function getLookupData(TableConfig $tableConfig): array
    {
        $customObjectFilter = $tableConfig->getFilter(CustomItem::class, 'customObject');
        $queryBuilder       = $this->customItemRepository->getTableDataQuery($tableConfig);
        $queryBuilder       = $this->applyOwnerFilter($queryBuilder, $customObjectFilter->getValue());
        $rootAlias          = $queryBuilder->getRootAliases()[0];
        $queryBuilder->select("{$rootAlias}.name as value, {$rootAlias}.id");

        return array_values($queryBuilder->getQuery()->getArrayResult());
    }

    /**
     * @param CustomItem $customItem
     *
     * @return CustomItem
     */
    public function populateCustomFields(CustomItem $customItem): CustomItem
    {
        $values            = $customItem->getCustomFieldValues();
        $customFields      = $this->customFieldModel->fetchCustomFieldsForObject($customItem->getCustomObject());
        $customFieldValues = $this->customFieldValueModel->getValuesForItem($customItem);

        foreach ($customFieldValues as $customFieldValue) {
            $values->set($customFieldValue->getId(), $customFieldValue);
        }

        /** @var CustomField $customField */
        foreach ($customFields as $customField) {
            // Create default value for field that does not exist yet.
            try {
                $customItem->findCustomFieldValueForFieldId($customField->getId());
            } catch (NotFoundException $e) {
                $customFieldType = $this->customFieldTypeProvider->getType($customField->getType());
                $values->set(
                    $customField->getId(),
                    $customFieldType->createValueEntity($customField, $customItem, $customField->getDefaultValue())
                );
            }
        }

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
     * @param CustomField $customField
     * @param Lead        $contact
     * @param string      $expr
     * @param mixed       $value
     *
     * @return int
     */
    public function findItemIdForValue(CustomField $customField, Lead $contact, string $expr, $value): int
    {
        return $this->customItemRepository->findItemIdForValue($customField, $contact, $expr, $value);
    }

    /**
     * @param Lead         $contact
     * @param CustomObject $customObject
     *
     * @return int
     */
    public function countItemsLinkedToContact(CustomObject $customObject, Lead $contact): int
    {
        return $this->customItemRepository->countItemsLinkedToContact($customObject, $contact);
    }

    /**
     * Used only by Mautic's generic methods. Use CustomItemPermissionProvider instead.
     *
     * @return string
     */
    public function getPermissionBase(): string
    {
        return 'custom_objects:custom_items';
    }

    /**
     * Adds condition for owner if the user doesn't have permissions to view other.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    private function applyOwnerFilter(QueryBuilder $queryBuilder, int $customObjectId): QueryBuilder
    {
        try {
            $this->permissionProvider->isGranted('viewother', $customObjectId);
        } catch (ForbiddenException $e) {
            $this->customItemRepository->applyOwnerId($queryBuilder, $this->userHelper->getUser()->getId());
        }

        return $queryBuilder;
    }
}
