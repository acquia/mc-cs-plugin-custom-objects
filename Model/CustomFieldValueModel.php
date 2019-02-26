<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Model;

use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use Doctrine\ORM\EntityManager;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Doctrine\Common\Collections\ArrayCollection;

class CustomFieldValueModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomFieldTypeProvider
     */
    private $customFieldTypeProvider;

    /**
     * @param EntityManager           $entityManager
     * @param CustomFieldTypeProvider $customFieldTypeProvider
     */
    public function __construct(
        EntityManager $entityManager,
        CustomFieldTypeProvider $customFieldTypeProvider
    )
    {
        $this->entityManager           = $entityManager;
        $this->customFieldTypeProvider = $customFieldTypeProvider;
    }

    /**
     * The values are joined from several tables. Each value type can have own table.
     *
     * @param CustomItem $customItem
     *
     * @return ArrayCollection
     */
    public function getValuesForItem(CustomItem $customItem): ArrayCollection
    {
        $customFieldValues = new ArrayCollection();

        if (!$customItem->getId()) {
            return $customFieldValues;
        }

        $fieldTypes = $this->customFieldTypeProvider->getTypes();
        $queries    = [];
        $params     = [];

        foreach ($fieldTypes as $type) {
            $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
            $queryBuilder->select("{$type->getTableAlias()}.custom_field_id, {$type->getTableAlias()}.value, '{$type->getKey()}' AS type");
            $queryBuilder->from($type->getTableName(), $type->getTableAlias());
            $queryBuilder->where("{$type->getTableAlias()}.custom_item_id = :customItemId");
            $params['customItemId'] = $customItem->getId();
            $queries[]              = $queryBuilder->getSQL();
        }

        $statement = $this->entityManager->getConnection()->prepare(implode(' UNION ', $queries));

        foreach ($params as $param => $value) {
            $statement->bindValue($param, $value);
        }

        $statement->execute();
        $rows = $statement->fetchAll();

        foreach ($rows as $row) {
            $fieldType      = $this->customFieldTypeProvider->getType($row['type']);
            $entityClass    = $fieldType->getEntityClass();
            $customFieldRef = $this->entityManager->getReference(CustomField::class, (int) $row['custom_field_id']);
            $customFieldRef->setTypeObject($fieldType);
            $customFieldValueRef = $this->entityManager->getReference($entityClass, ['customField' => $customFieldRef, 'customItem' => $customItem]);
            $customFieldValueRef->setValue($row['value']);
            $customFieldValueRef->updateThisEntityManually();
            $customFieldValues->set($customFieldValueRef->getId(), $customFieldValueRef);
        }

        return $customFieldValues;
    }

    /**
     * If the entities were created manually, not fetched by Entity Manager
     * then we have to update them manually without help of EntityManager.
     *
     * @param CustomFieldValueInterface $customFieldValue
     */
    public function save(CustomFieldValueInterface $customFieldValue): void
    {
        if ($customFieldValue->shouldBeUpdatedManually()) {
            $this->updateManually($customFieldValue);
            $this->entityManager->detach($customFieldValue);
        } else {
            $this->entityManager->persist($customFieldValue);
        }
    }

    /**
     * @param CustomFieldValueInterface $customFieldValue
     */
    public function updateManually(CustomFieldValueInterface $customFieldValue): void
    {
        $fieldType    = $customFieldValue->getCustomField()->getTypeObject();
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update($fieldType->getEntityClass(), $fieldType->getTableAlias())
            ->set("{$fieldType->getTableAlias()}.value", ':value')
            ->where("{$fieldType->getTableAlias()}.customField = :customFieldId")
            ->andWhere("{$fieldType->getTableAlias()}.customItem = :customItemId")
            ->setParameter('value', $customFieldValue->getValue())
            ->setParameter('customFieldId', (int) $customFieldValue->getCustomField()->getId())
            ->setParameter('customItemId', (int) $customFieldValue->getCustomItem()->getId());
        $query = $queryBuilder->getQuery();
        $query->execute();
    }
}
