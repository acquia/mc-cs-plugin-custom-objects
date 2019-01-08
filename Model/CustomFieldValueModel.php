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
use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInt;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\CustomFieldTypeInterface;

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
     * @return array
     */
    public function getValuesForItem(CustomItem $customItem): array
    {
        if (!$customItem->getId()) {
            return [];
        }

        $qb         = $this->entityManager->createQueryBuilder();
        $fieldTypes = $this->customFieldTypeProvider->getTypes();
        $firstType  = array_shift($fieldTypes);
        $or         = $qb->expr()->orX();

        $qb->from($firstType->getEntityClass(), $this->getAlias($firstType));
        $qb->select($this->getAlias($firstType));
        $or->add($qb->expr()->eq("{$this->getAlias($firstType)}.customItem", ':customItem'));

        foreach ($fieldTypes as $type) {
            $qb->leftJoin($type->getEntityClass(), $this->getAlias($type));
            $qb->addSelect($this->getAlias($type));
            $or->add($qb->expr()->eq("{$this->getAlias($type)}.customItem", ':customItem'));
        }

        $qb->where($or);
        $qb->setParameter('customItem', $customItem->getId());

        return $qb->getQuery()->getResult();
    }

    /**
     * Create unique table alias for field type.
     *
     * @param CustomFieldTypeInterface $fieldType
     * 
     * @return string
     */
    private function getAlias(CustomFieldTypeInterface $fieldType): string
    {
        return "cfv{$fieldType->getKey()}";
    }
}
