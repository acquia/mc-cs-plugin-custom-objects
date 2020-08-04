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

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;

class CustomFieldRepository extends CommonRepository
{
    public function isAliasUnique(string $alias, ?int $id = null): bool
    {
        $q = $this->createQueryBuilder(CustomField::TABLE_ALIAS);
        $q->select('count('.CustomField::TABLE_ALIAS.'.id) as alias_count');
        $q->where(CustomField::TABLE_ALIAS.'.alias = :alias');
        $q->setParameter('alias', $alias);

        if (null !== $id) {
            $q->andWhere($q->expr()->neq(CustomField::TABLE_ALIAS.'.id', ':ignoreId'));
            $q->setParameter('ignoreId', $id);
        }

        return (bool) $q->getQuery()->getSingleResult()['alias_count'];
    }

    /**
     * @return ArrayCollection|CustomField[]
     */
    public function getRequiredCustomFieldsForCustomObject(int $customObjectId): ArrayCollection
    {
        $queryBuilder = $this->createQueryBuilder(CustomField::TABLE_ALIAS);
        $queryBuilder->where(CustomField::TABLE_ALIAS.'.customObject = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObjectId);
        $queryBuilder->andWhere(CustomField::TABLE_ALIAS.'.required = :required');
        $queryBuilder->setParameter('required', true);

        $query = $queryBuilder->getQuery();

        return new ArrayCollection($query->getResult());
    }

    public function getCustomFieldTypeById(int $customFieldId): string
    {
        $customField = $this->findOneBy(['id' => $customFieldId]);

        return $customField ? $customField->getType() : '';
    }
}
