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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use MauticPlugin\CustomObjectsBundle\Provider\ConfigProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomFieldFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var QueryFilterHelper
     */
    private $filterHelper;

    /**
     * @see ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT
     *
     * @var int
     */
    private $relationLimit;

    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher,
        QueryFilterHelper $filterHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);

        $this->filterHelper  = $filterHelper;
        $this->relationLimit = $coreParametersHelper->get(ConfigProvider::CONFIG_PARAM_ITEM_VALUE_TO_CONTACT_RELATION_LIMIT);
    }

    /** {@inheritdoc} */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.custom_field.value';
    }

    /**
     * @throws NotFoundException
     */
    public function applyQuery(SegmentQueryBuilder $queryBuilder, ContactSegmentFilter $filter): SegmentQueryBuilder
    {
        $filterOperator = $filter->getOperator();

        $tableAlias = 'cfwq_'.(int) $filter->getField();

        $unionQueryContainer = $this->filterHelper->createValueQuery(
            $tableAlias,
            $filter
        );

        foreach($unionQueryContainer as $segmentQueryBuilder) {
            $segmentQueryBuilder->andWhere(
                $segmentQueryBuilder->expr()->eq("{$tableAlias}_contact.contact_id", 'l.id')
            );
        }

        switch ($filterOperator) {
            case 'empty':
            case 'neq':
            case 'notLike':
            case '!multiselect':
                $queryBuilder->addLogic(
                    $queryBuilder->expr()->notExists($unionQueryContainer->getSQL()),
                    $filter->getGlue()
                );

                break;
            default:
                $queryBuilder->addLogic(
                    $queryBuilder->expr()->exists($unionQueryContainer->getSQL()),
                    $filter->getGlue()
                );
        }

        $queryBuilder->setParameters($unionQueryContainer->getParameters(), $unionQueryContainer->getParameterTypes());

        return $queryBuilder;
    }
}
