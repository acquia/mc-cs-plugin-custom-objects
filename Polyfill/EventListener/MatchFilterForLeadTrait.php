<?php

namespace MauticPlugin\CustomObjectsBundle\Polyfill\EventListener;

use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
use Mautic\LeadBundle\Segment\OperatorOptions;

/**
 * Polyfill for \Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait.
 */
trait MatchFilterForLeadTrait
{
    use \Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;

    private LeadListRepository $segmentRepository;

    /**
     * @param mixed[] $filter
     * @param mixed[] $lead
     */
    protected function matchFilterForLead(array $filter, array $lead): bool
    {
        if (empty($lead['id'])) {
            // Lead in generated for preview with faked data
            return false;
        }

        $groups   = [];
        $groupNum = 0;

        foreach ($filter as $data) {
            if (!array_key_exists($data['field'], $lead)) {
                continue;
            }

            /*
             * Split the filters into groups based on the glue.
             * The first filter and any filters whose glue is
             * "or" will start a new group.
             */
            if (0 === $groupNum || 'or' === $data['glue']) {
                ++$groupNum;
                $groups[$groupNum] = null;
            }

            /*
             * If the group has been marked as false, there
             * is no need to continue checking the others
             * in the group.
             */
            if (false === $groups[$groupNum]) {
                continue;
            }

            /*
             * If we are checking the first filter in a group
             * assume that the group will not match.
             */
            if (null === $groups[$groupNum]) {
                $groups[$groupNum] = false;
            }

            $leadValues   = $lead[$data['field']];
            $leadValues   = 'custom_object' === $data['object'] ? $leadValues : [$leadValues];
            $filterVal    = $data['filter'];
            $subgroup     = null;

            if (is_array($leadValues)) {
                foreach ($leadValues as $leadVal) {
                    if ($subgroup) {
                        break;
                    }

                    switch ($data['type']) {
                        case 'boolean':
                            if (null !== $leadVal) {
                                $leadVal = (bool) $leadVal;
                            }

                            if (null !== $filterVal) {
                                $filterVal = (bool) $filterVal;
                            }
                            break;
                        case 'datetime':
                        case 'time':
                            if (!is_null($leadVal) && !is_null($filterVal)) {
                                $leadValCount   = substr_count($leadVal, ':');
                                $filterValCount = substr_count($filterVal, ':');

                                if (2 === $leadValCount && 1 === $filterValCount) {
                                    $filterVal .= ':00';
                                }
                            }
                            break;
                        case 'tags':
                        case 'select':
                        case 'multiselect':
                            if (!is_array($leadVal) && !empty($leadVal)) {
                                $leadVal = explode('|', $leadVal);
                            }
                            if (!is_null($filterVal) && !is_array($filterVal)) {
                                $filterVal = explode('|', $filterVal);
                            }
                            break;
                        case 'number':
                            $leadVal   = (int) $leadVal;
                            $filterVal = (int) $filterVal;
                            break;
                    }

                    switch ($data['operator']) {
                        case '=':
                            if ('boolean' === $data['type']) {
                                $groups[$groupNum] = $leadVal === $filterVal;
                            } else {
                                $groups[$groupNum] = $leadVal == $filterVal;
                            }
                            break;
                        case '!=':
                            if ('boolean' === $data['type']) {
                                $groups[$groupNum] = $leadVal !== $filterVal;
                            } else {
                                $groups[$groupNum] = $leadVal != $filterVal;
                            }
                            break;
                        case 'gt':
                            $groups[$groupNum] = $leadVal > $filterVal;
                            break;
                        case 'gte':
                            $groups[$groupNum] = $leadVal >= $filterVal;
                            break;
                        case 'lt':
                            $groups[$groupNum] = $leadVal < $filterVal;
                            break;
                        case 'lte':
                            $groups[$groupNum] = $leadVal <= $filterVal;
                            break;
                        case 'empty':
                            $groups[$groupNum] = empty($leadVal);
                            break;
                        case '!empty':
                            $groups[$groupNum] = !empty($leadVal);
                            break;
                        case 'like':
                            $matchVal          = str_replace(['.', '*', '%'], ['\.', '\*', '.*'], $filterVal);
                            $groups[$groupNum] = 1 === preg_match('/'.$matchVal.'/', $leadVal);
                            break;
                        case '!like':
                            $matchVal          = str_replace(['.', '*'], ['\.', '\*'], $filterVal);
                            $matchVal          = str_replace('%', '.*', $matchVal);
                            $groups[$groupNum] = 1 !== preg_match('/'.$matchVal.'/', $leadVal);
                            break;
                        case OperatorOptions::IN:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, false);
                            break;
                        case OperatorOptions::NOT_IN:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, true);
                            break;
                        case 'regexp':
                            $groups[$groupNum] = 1 === preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case '!regexp':
                            $groups[$groupNum] = 1 !== preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case 'startsWith':
                            $groups[$groupNum] = 0 === strncmp($leadVal, $filterVal, strlen($filterVal));
                            break;
                        case 'endsWith':
                            $endOfString       = substr($leadVal, strlen($leadVal) - strlen($filterVal));
                            $groups[$groupNum] = 0 === strcmp($endOfString, $filterVal);
                            break;
                        case 'contains':
                            $groups[$groupNum] = false !== strpos((string) $leadVal, (string) $filterVal);
                            break;
                        default:
                            throw new OperatorsNotFoundException('Operator is not defined or invalid operator found.');
                    }

                    $subgroup = $groups[$groupNum];
                }
            }
        }

        return in_array(true, $groups);
    }

    /**
     * @param mixed[] $data
     * @param mixed[] $lead
     *
     * @return ?mixed[]
     */
    private function transformFilterDataForLead(array $data, array $lead): ?array
    {
        return null;
    }

    /**
     * @param mixed[] $filters
     */
    private function doFiltersContainCompanyFilter(array $filters): bool
    {
        foreach ($filters as $filter) {
            $object = $filter['object'] ?? '';

            if ('company' === $object) {
                return true;
            }

            if ((0 === strpos($filter['field'], 'company') && 'company' !== $filter['field'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed[] $filters
     */
    private function doFiltersContainTagsFilter(array $filters): bool
    {
        foreach ($filters as $filter) {
            if ('tags' === ($filter['type'] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
