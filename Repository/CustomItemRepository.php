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

namespace MauticPlugin\CustomObjectsBundle\Repository;

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use Doctrine\ORM\QueryBuilder;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;

class CustomItemRepository extends CommonRepository
{
    /**
     * @param TableConfig $tableConfig
     * 
     * @return QueryBuilder
     */
    public function getTableDataQuery(TableConfig $tableConfig): QueryBuilder
    {
        $alias        = self::getAlias();
        $queryBuilder = $this->createQueryBuilder($alias, $alias.'.id');
        $queryBuilder->select($alias);
        $queryBuilder->orderBy($tableConfig->getOrderBy(), $tableConfig->getOrderDirection());

        return $this->applyTableFilters($queryBuilder, $tableConfig);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param TableConfig $tableConfig
     * 
     * @return QueryBuilder
     */
    public function applyTableFilters(QueryBuilder $queryBuilder, TableConfig $tableConfig): QueryBuilder
    {
        $metadata  = $this->getClassMetadata();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        foreach ($tableConfig->getFilters() as $entityClass => $filters) {
            foreach ($filters as $filter) {
                if (!in_array($filter->getTableAlias(), $queryBuilder->getAllAliases())) {
                    $cloumnNameArr = array_keys($metadata->getAssociationsByTargetClass($filter->getEntityName()));
                    if (empty($cloumnNameArr[0])) {
                        throw new \UnexpectedValueException("Entity {$this->getEntityName()} does not have association with {$filter->getEntityName()}");
                    }
                    $queryBuilder->innerJoin($rootAlias.'.'.$cloumnNameArr[0], $filter->getTableAlias());
                }
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->{$filter->getExpression()}($filter->getTableAlias().'.'.$filter->getColumnName(), ':'.$filter->getColumnName())
                );
                $queryBuilder->setParameter($filter->getColumnName(), $filter->getValue());
            }
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param integer $userId
     * 
     * @return QueryBuilder
     */
    public function applyOwnerId(QueryBuilder $queryBuilder, int $userId): QueryBuilder
    {
        return $queryBuilder->andWhere(self::getAlias().'.createdBy', $userId);
    }

    /**
     * @param string $repositoryName
     * 
     * @return string
     */
    public static function getAlias(string $repositoryName = null): string
    {
        if (null === $repositoryName) {
            $repositoryName = self::class;
        }
        $path = explode('\\', $repositoryName);
        return rtrim(end($path), 'Repository');
    }

    /**
     * @param Lead         $contact
     * @param CustomObject $customObject
     * 
     * @return int
     */
    public function countItemsLinkedToContact(CustomObject $customObject, Lead $contact): int
    {
        $queryBuilder = $this->createQueryBuilder('ci', 'ci.id');
        $queryBuilder->select('COUNT(ci.id) as linkedItemsCount');
        $queryBuilder->innerJoin('ci.contactReferences', 'cixctct');
        $queryBuilder->where('ci.customObject = :customObjectId');
        $queryBuilder->andWhere('cixctct.contact = :contactId');
        $queryBuilder->setParameter('customObjectId', $customObject->getId());
        $queryBuilder->setParameter('contactId', $contact->getId());
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return (int) $result;
    }
}
