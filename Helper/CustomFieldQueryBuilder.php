<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use MauticPlugin\CustomObjectsBundle\Provider\CustomFieldTypeProvider;
use MauticPlugin\CustomObjectsBundle\Repository\CustomFieldRepository;
use MauticPlugin\CustomObjectsBundle\Segment\Query\UnionQueryContainer;

class CustomFieldQueryBuilder
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomFieldTypeProvider
     */
    private $fieldTypeProvider;

    /**
     * @var int
     */
    private $itemRelationLevelLimit;

    /**
     * @var UnionQueryContainer|null
     */
    private $unionQueryContainer;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    public function __construct(
        EntityManager $entityManager,
        CustomFieldTypeProvider $fieldTypeProvider,
        CoreParametersHelper $coreParametersHelper,
        CustomFieldRepository $customFieldRepository
    ){
        $this->entityManager = $entityManager;
        $this->fieldTypeProvider = $fieldTypeProvider;
        $this->itemRelationLevelLimit = (int) $coreParametersHelper->get(ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT);
        $this->customFieldRepository = $customFieldRepository;
    }

    public function buildQuery(
        string $alias,
        ContactSegmentFilter $segmentFilter
    ): UnionQueryContainer {
        $segmentFilterFieldId   = (int) $segmentFilter->getField();
        $segmentFilterFieldType = $segmentFilter->getType();
        $segmentFilterFieldType = $segmentFilterFieldType ?: $this->customFieldRepository->getCustomFieldTypeById($segmentFilterFieldId);
        // This value is prefixed
        $dataTable              = $this->fieldTypeProvider->getType($segmentFilterFieldType)->getTableName();

        $this->unionQueryContainer = new UnionQueryContainer();
        $this->create1LevelQuery($alias, $segmentFilterFieldId, $dataTable);

        $level = 2;
        while ($level <= $this->itemRelationLevelLimit) {
            $this->createMultilevelQueries($alias, $segmentFilterFieldId, $dataTable, 2);
            $level ++;
        }

        return $this->unionQueryContainer;
    }

    private function create1LevelQuery(string $alias, int $segmentFilterFieldId, string $dataTable): void
    {
        $qb = new SegmentQueryBuilder($this->entityManager->getConnection());
        $qb
            ->select('contact_id')
            ->from($dataTable, "{$alias}_value")
            ->innerJoin(
                "{$alias}_value",
                MAUTIC_TABLE_PREFIX.'custom_item_xref_contact',
                "{$alias}_contact",
                "{$alias}_value.custom_item_id = {$alias}_contact.custom_item_id"
            )
            ->andWhere(
                $qb->expr()->eq("{$alias}_value.custom_field_id", ":{$alias}_custom_field_id")
            )
            ->setParameter(":{$alias}_custom_field_id", $segmentFilterFieldId);

        $this->unionQueryContainer->add($qb);
    }

    private function createMultilevelQueries(string $alias, int $segmentFilterFieldId, string $dataTable, int $currentLevel): void
    {
        if ($this->itemRelationLevelLimit > 1 && $currentLevel === 2) { // @todo only second level implemented now
            $qb = new SegmentQueryBuilder($this->entityManager->getConnection());
            $qb
                ->select('contact_id')
                ->from($dataTable, "{$alias}_value")
                ->innerJoin(
                    "{$alias}_value",
                    MAUTIC_TABLE_PREFIX.'custom_item_xref_custom_item',
                    "{$alias}_item_xref",
                    "{$alias}_item_xref.custom_item_id_lower = {$alias}_value.custom_item_id"
                )
                ->innerJoin(
                    "{$alias}_value",
                    MAUTIC_TABLE_PREFIX.'custom_item_xref_contact',
                    "{$alias}_contact",
                    "{$alias}_item_xref.custom_item_id_higher = {$alias}_contact.custom_item_id"
                )
                ->andWhere(
                    $qb->expr()->eq("{$alias}_value.custom_field_id", ":{$alias}_custom_field_id")
                )
                ->setParameter(":{$alias}_custom_field_id", $segmentFilterFieldId);

            $this->unionQueryContainer->add($qb);

            $qb = new SegmentQueryBuilder($this->entityManager->getConnection());
            $qb
                ->select('contact_id')
                ->from($dataTable, "{$alias}_value")
                ->innerJoin(
                    "{$alias}_value",
                    MAUTIC_TABLE_PREFIX.'custom_item_xref_custom_item',
                    "{$alias}_item_xref",
                    "{$alias}_item_xref.custom_item_id_higher = {$alias}_value.custom_item_id"
                )
                ->innerJoin(
                    "{$alias}_value",
                    MAUTIC_TABLE_PREFIX.'custom_item_xref_contact',
                    "{$alias}_contact",
                    "{$alias}_item_xref.custom_item_id_lower = {$alias}_contact.custom_item_id"
                )
                ->andWhere(
                    $qb->expr()->eq("{$alias}_value.custom_field_id", ":{$alias}_custom_field_id")
                )
                ->setParameter(":{$alias}_custom_field_id", $segmentFilterFieldId);

            $this->unionQueryContainer->add($qb);
        }
    }
}
