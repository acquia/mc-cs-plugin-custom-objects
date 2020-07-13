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

use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomObjectRepository extends CommonRepository
{
    public function checkAliasExists(string $alias, ?int $id = null): bool
    {
        $q = $this->createQueryBuilder(CustomObject::TABLE_ALIAS);
        $q->select('count('.CustomObject::TABLE_ALIAS.'.id) as alias_count');
        $q->where(CustomObject::TABLE_ALIAS.'.alias = :alias');
        $q->setParameter('alias', $alias);

        if (null !== $id) {
            $q->andWhere($q->expr()->neq(CustomObject::TABLE_ALIAS.'.id', ':ignoreId'));
            $q->setParameter('ignoreId', $id);
        }

        return (bool) $q->getQuery()->getSingleResult()['alias_count'];
    }

    /**
     * Used for the CustomObjectType form to load masterObject choices.
     * Should only load custom objects with type = TYPE_MASTER, that are not the current
     * object being edited, and that do not already have a relationship associated.
     */
    public function getMasterObjectChoices(CustomObject $customObject = null): array
    {
        $qb = $this->createQueryBuilder(CustomObject::TABLE_ALIAS);
        $qb->select('partial '.CustomObject::TABLE_ALIAS.'.{id,nameSingular}');
        $qb->where($qb->expr()->eq(CustomObject::TABLE_ALIAS.'.type', ':type'));
        $qb->setParameter('type', CustomObject::TYPE_MASTER);

        if ($customObject && null !== $customObject->getId()) {
            $qb->andWhere($qb->expr()->neq(CustomObject::TABLE_ALIAS.'.id', ':ignoreId'));
            $qb->setParameter('ignoreId', $customObject->getId());
        }

        $sqb = $this->createQueryBuilder('subQuery');
        $sqb->where('subQuery.masterObject = '.CustomObject::TABLE_ALIAS.'.id');

        if ($customObject && $customObject->getMasterObject()) {
            $sqb->andWhere('subQuery.masterObject != :currentMasterObject');
            $qb->setParameter('currentMasterObject', $customObject->getMasterObject()->getId());
        }

        $qb->andWhere($qb->expr()->not($qb->expr()->exists($sqb->getDQL())));

        $objects = $qb->getQuery()->getResult();
        $choices = [];

        /** @var CustomObject $object */
        foreach ($objects as $object) {
            $choices["{$object->getNameSingular()} ({$object->getAlias()})"] = $object->getId();
        }

        return $choices;
    }

    /**
     * Used by internal Mautic methods. Use the constant directly instead.
     */
    public function getTableAlias(): string
    {
        return CustomObject::TABLE_ALIAS;
    }
}
