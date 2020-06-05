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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidArgumentException;
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
     * @throws DBALException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $filterOperator = $filter->getOperator();
        $filterFieldId  = $filter->getField();

        $tableAlias = 'cfwq_'.(int) $filter->getField();

        $filterQueryBuilder = $this->filterHelper->createValueQueryBuilder(
            $queryBuilder->getConnection(),
            $tableAlias,
            (int) $filter->getField(),
            $filter->getType()
        );
        $this->filterHelper->addCustomFieldValueExpressionFromSegmentFilter($filterQueryBuilder, $tableAlias, $filter);

        $filterQueryBuilder->select($tableAlias.'_contact.contact_id as lead_id');
        $filterQueryBuilder->andWhere('l.id = '.$tableAlias.'_contact.contact_id');

        $queryBuilder->setParameter('customFieldId_'.$tableAlias, (int) $filterFieldId);

        switch ($filterOperator) {
            case 'empty':
            case 'neq':
            case 'notLike':
            case '!multiselect':
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($filterQueryBuilder->getSQL()), $filter->getGlue());

                break;
            default:
                $queryBuilder->addLogic($queryBuilder->expr()->exists($filterQueryBuilder->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParameters($filterQueryBuilder->getParameters(), $filterQueryBuilder->getParameterTypes());

        return $queryBuilder;
    }
}
