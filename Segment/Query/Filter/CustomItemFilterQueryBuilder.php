<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Helper\QueryBuilderManipulatorTrait;

class CustomItemFilterQueryBuilder extends BaseFilterQueryBuilder
{
    use QueryBuilderManipulatorTrait;

    /**
     * @var QueryFilterHelper
     */
    private $filterHelper;

    /**
     * @param RandomParameterName $randomParameterNameService
     * @param QueryFilterHelper   $filterHelper
     */
    public function __construct(RandomParameterName $randomParameterNameService, QueryFilterHelper $filterHelper)
    {
        parent::__construct($randomParameterNameService);
        $this->filterHelper = $filterHelper;
    }

    /**
     * @return string
     */
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.custom_item.value';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $filterFieldId = $filter->getField();

        $tableAlias = 'cin_'.(int) $filter->getField();

        $filterQueryBuilder = $this->filterHelper->createItemNameQueryBuilder(
            $queryBuilder->getConnection(),
            $tableAlias
        );

        $filterQueryBuilder->andWhere(
            $filterQueryBuilder->expr()->eq($tableAlias."_item.id", ":" . $tableAlias."ItemId")
        );

        $this->filterHelper->addCustomObjectNameExpression(
            $filterQueryBuilder,
            $tableAlias,
            $filter->getOperator(),
            $filter->getParameterValue()
        );

        $filterQueryBuilder->select($tableAlias.'_contact.contact_id as lead_id');
        $filterQueryBuilder->andWhere('l.id = '.$tableAlias.'_contact.contact_id');

        $queryBuilder->setParameter($tableAlias."ItemId", (int) $filterFieldId);

        switch ($filter->getOperator()) {
            case 'empty':
            case 'neq':
            case 'notLike':
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($filterQueryBuilder->getSQL()), $filter->getGlue());

                break;
            default:
                $queryBuilder->addLogic($queryBuilder->expr()->exists($filterQueryBuilder->getSQL()), $filter->getGlue());
        }

        $this->copyParams($filterQueryBuilder, $queryBuilder);

        return $queryBuilder;
    }

    /**
     * @param string[]|string $filterParameters
     *
     * @return string[]|string
     */
    public function getParametersAliases($filterParameters)
    {
        if (is_array($filterParameters)) {
            return array_map(function () {
                return $this->generateRandomParameterName();
            }, $filterParameters);
        }

        return $this->generateRandomParameterName();
    }
}
