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

use Doctrine\DBAL\DBALException;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\QueryFilterHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomFieldFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var QueryFilterHelper
     */
    private $filterHelper;

    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher,
        QueryFilterHelper $filterHelper
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);

        $this->filterHelper  = $filterHelper;
    }

    /** {@inheritdoc} */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.custom_field.value';
    }

    /**
     * @throws DBALException
     */
    public function applyQuery(SegmentQueryBuilder $queryBuilder, ContactSegmentFilter $filter): SegmentQueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator = $filter->getOperator();

        $tableAlias = 'cfwq_'.(int) $filter->getField();

        $unionQueryContainer = $this->filterHelper->createValueQuery(
            $tableAlias,
            $filter
        );

        foreach ($unionQueryContainer as $segmentQueryBuilder) {
            $segmentQueryBuilder->andWhere(
                $segmentQueryBuilder->expr()->eq("{$tableAlias}_contact.contact_id", $leadsTableAlias.'.id')
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
