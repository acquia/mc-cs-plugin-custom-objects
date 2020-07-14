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
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
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
    public function applyQuery(SegmentQueryBuilder $queryBuilder, ContactSegmentFilter $filter): SegmentQueryBuilder
    {
        $filterOperator = $filter->getOperator();

        $tableAlias = 'cfwq_'.(int) $filter->getField();

        [$queryString, $parameters, $parameterTypes] = $this->filterHelper->createValueQuery(
            $queryBuilder->getConnection(),
            $tableAlias,
            $filter
        );

        switch ($filterOperator) {
            case 'empty':
            case 'neq':
            case 'notLike':
            case '!multiselect':
                $queryBuilder->addLogic($queryBuilder->expr()->notExists($queryString), $filter->getGlue());

                break;
            default:
                $queryBuilder->addLogic($queryBuilder->expr()->exists($queryString), $filter->getGlue());
        }

        foreach ($parameters as $key => $value) {
            $parameterType = $parameterTypes[$key] ?? null;
            $queryBuilder->setParameter($key, $value, $parameterType);
        }

        $queryBuilder->setParameters($parameters);

        return $queryBuilder;
    }
}
