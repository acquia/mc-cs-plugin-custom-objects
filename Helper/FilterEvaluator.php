<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
use Mautic\LeadBundle\Segment\OperatorOptions;

/**
 * Evaluates DWC filter conditions against a lead's data, including multi-value custom object fields.
 *
 * Unlike Mautic's MatchFilterForLeadTrait (which expects scalar values), this service handles
 * custom object fields where $lead['cmf_123'] is an array of values from multiple linked items.
 * For such fields, a filter matches if ANY linked item's value satisfies the condition.
 *
 * @phpstan-type FilterArray array{glue: string, field: string, object: string, type: string, filter: mixed, operator: string}
 * @phpstan-type LeadArray array<string, mixed>
 */
class FilterEvaluator
{
    /**
     * @param array<FilterArray> $filters
     * @param LeadArray          $lead
     *
     * @throws OperatorsNotFoundException
     */
    public function evaluate(array $filters, array $lead): bool
    {
        if (empty($lead['id'])) {
            return false;
        }

        /** @var array<int, bool|null> $groups */
        $groups   = [];
        $groupNum = 0;

        foreach ($filters as $data) {
            if (!array_key_exists($data['field'], $lead)) {
                continue;
            }

            if (0 === $groupNum || 'or' === $data['glue']) {
                ++$groupNum;
                $groups[$groupNum] = null;
            }

            if (false === $groups[$groupNum]) {
                continue;
            }

            if (null === $groups[$groupNum]) {
                $groups[$groupNum] = false;
            }

            $isCustomObject = 'custom_object' === $data['object'];
            $leadValues     = $isCustomObject ? $lead[$data['field']] : [$lead[$data['field']]];
            $filterVal      = $data['filter'];

            if (!is_array($leadValues)) {
                $leadValues = [$leadValues];
            }

            // No linked custom items: only 'empty' operator can match.
            if ($isCustomObject && [] === $leadValues) {
                $groups[$groupNum] = 'empty' === $data['operator'];
                continue;
            }

            $matched = false;
            foreach ($leadValues as $leadVal) {
                [$leadVal, $filterVal] = $this->coerceTypes($data['type'], $leadVal, $filterVal);
                if ($this->applyOperator($data['operator'], $data['type'], $leadVal, $filterVal)) {
                    $matched = true;
                    break;
                }
            }

            $groups[$groupNum] = $matched;
        }

        return in_array(true, $groups, true);
    }

    /**
     * @return array{mixed, mixed}
     */
    private function coerceTypes(string $type, mixed $leadVal, mixed $filterVal): array
    {
        switch ($type) {
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
                if (null !== $leadVal && null !== $filterVal) {
                    if (2 === substr_count($leadVal, ':') && 1 === substr_count($filterVal, ':')) {
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
                if (null !== $filterVal && !is_array($filterVal)) {
                    $filterVal = explode('|', $filterVal);
                }
                break;
            case 'number':
                $leadVal   = (int) $leadVal;
                $filterVal = (int) $filterVal;
                break;
        }

        return [$leadVal, $filterVal];
    }

    /**
     * @throws OperatorsNotFoundException
     */
    private function applyOperator(string $operator, string $type, mixed $leadVal, mixed $filterVal): bool
    {
        switch ($operator) {
            case '=':
                return 'boolean' === $type ? $leadVal === $filterVal : $leadVal == $filterVal;
            case '!=':
                return 'boolean' === $type ? $leadVal !== $filterVal : $leadVal != $filterVal;
            case 'gt':
                return $leadVal > $filterVal;
            case 'gte':
                return $leadVal >= $filterVal;
            case 'lt':
                return $leadVal < $filterVal;
            case 'lte':
                return $leadVal <= $filterVal;
            case 'empty':
                return empty($leadVal);
            case '!empty':
                return !empty($leadVal);
            case 'like':
                $pattern = str_replace(['.', '*', '%'], ['\.', '\*', '.*'], $filterVal);

                return 1 === preg_match('/'.$pattern.'/', $leadVal);
            case '!like':
                $pattern = str_replace(['.', '*', '%'], ['\.', '\*', '.*'], $filterVal);

                return 1 !== preg_match('/'.$pattern.'/', $leadVal);
            case OperatorOptions::IN:
                return $this->checkLeadValueIsInFilter($leadVal, $filterVal, false);
            case OperatorOptions::NOT_IN:
                return $this->checkLeadValueIsInFilter($leadVal, $filterVal, true);
            case 'regexp':
                return 1 === preg_match('/'.$filterVal.'/i', $leadVal);
            case '!regexp':
                return 1 !== preg_match('/'.$filterVal.'/i', $leadVal);
            case 'startsWith':
                return str_starts_with($leadVal, $filterVal);
            case 'endsWith':
                return 0 === strcmp(substr($leadVal, strlen($leadVal) - strlen($filterVal)), $filterVal);
            case 'contains':
                return str_contains((string) $leadVal, (string) $filterVal);
            default:
                throw new OperatorsNotFoundException('Operator is not defined or invalid operator found.');
        }
    }

    private function checkLeadValueIsInFilter(mixed $leadVal, mixed $filterVal, bool $defaultFlag): bool
    {
        $leadVal   = !is_array($leadVal) ? [$leadVal] : $leadVal;
        $filterVal = !is_array($filterVal) ? [$filterVal] : $filterVal;
        $retFlag   = $defaultFlag;

        foreach ($leadVal as $v) {
            if (in_array($v, $filterVal)) {
                $retFlag = !$defaultFlag;
                break;
            }
        }

        return $retFlag;
    }
}
