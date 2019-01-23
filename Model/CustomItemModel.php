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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use MauticPlugin\CustomObjectsBundle\Provider\CustomItemPermissionProvider;
use MauticPlugin\CustomObjectsBundle\Exception\ForbiddenException;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;

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
     * @param EntityManager $entityManager
     * @param CustomItemRepository $customItemRepository
     * @param CustomItemPermissionProvider $permissionProvider
     * @param UserHelper $userHelper
     * @param CustomFieldModel $customFieldModel
     * @param CustomFieldValueModel $customFieldValueModel
     * @param CustomFieldTypeProvider $customFieldTypeProvider
     */
    public function __construct(
        EntityManager $entityManager,
        CustomItemRepository $customItemRepository,
        CustomItemPermissionProvider $permissionProvider,
        UserHelper $userHelper,
        CustomFieldModel $customFieldModel,
        CustomFieldValueModel $customFieldValueModel,
        CustomFieldTypeProvider $customFieldTypeProvider
    )
    {
        $this->entityManager           = $entityManager;
        $this->customItemRepository    = $customItemRepository;
        $this->permissionProvider      = $permissionProvider;
        $this->userHelper              = $userHelper;
        $this->customFieldModel        = $customFieldModel;
        $this->customFieldValueModel   = $customFieldValueModel;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * @param CustomItem $entity
     * 
     * @return CustomItem
     */
    public function save(CustomItem $entity): CustomItem
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

        foreach ($entity->getCustomFieldValues() as $value) {
            $this->entityManager->persist($value);
        }

        foreach ($entity->getContactReferences() as $reference) {
            $this->entityManager->persist($reference);
        }

        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param integer $customItemId
     * @param integer $contactId
     */
    public function linkContact(int $customItemId, int $contactId): void
    {
        $customItem = $this->entityManager->getReference(CustomItem::class, $customItemId);
        $contact    = $this->entityManager->getReference(Lead::class, $contactId);
        $entity     = new CustomItemXrefContact($customItem, $contact);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * @param integer $id
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
     * @return array
     */
    public function getTableData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->customItemRepository->getTableDataQuery($tableConfig);
        $queryBuilder = $this->applyOwnerFilter($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param TableConfig $tableConfig
     * 
     * @return integer
     */
    public function getCountForTable(TableConfig $tableConfig): int
    {
        $queryBuilder = $this->customItemRepository->getTableCountQuery($tableConfig);
        $queryBuilder = $this->applyOwnerFilter($queryBuilder);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param TableConfig $tableConfig
     * 
     * @return array
     */
    public function getLookupData(TableConfig $tableConfig): array
    {
        $queryBuilder = $this->customItemRepository->getTableDataQuery($tableConfig);
        $queryBuilder = $this->applyOwnerFilter($queryBuilder);
        $rootAlias    = $queryBuilder->getRootAliases()[0];
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
        $customFieldValues = $this->customFieldValueModel->getValuesForItem($customItem);
        $customFields      = $this->customFieldModel->fetchCustomFieldsForObject($customItem->getCustomObject());
        
        foreach ($customFieldValues as $customFieldValue) {
            $values->set($customFieldValue->getCustomField()->getId(), $customFieldValue);
        }
        
        foreach ($customFields as $customField) {
            // Create default value for field that does not exist yet.
            if (null === $values->get($customField->getId())) {
                $customFieldType = $this->customFieldTypeProvider->getType($customField->getType());
                // @todo the default value should come form the custom field.
                $values->set(
                    $customField->getId(),
                    $customFieldType->createValueEntity($customField, $customItem)
                );
            }
        }

        return $customItem;
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
     * Used only by Mautic's generic methods. Use DI instead.
     * 
     * @return CommonRepository
     */
    public function getRepository(): CommonRepository
    {
        return $this->customItemRepository;
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
     * Used only by Mautic's generic methods. Use CustomFieldPermissionProvider instead.
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
    private function applyOwnerFilter(QueryBuilder $queryBuilder): QueryBuilder
    {
        try {
            $this->permissionProvider->isGranted('viewother');
        } catch (ForbiddenException $e) {
            $this->customItemRepository->applyOwnerFilter($queryBuilder, $this->userHelper->getUser()->getId());
        }

        return $queryBuilder;
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

            $args['filter']['force'] += $limitOwnerFilter;
        }

        return $args;
    }
}
