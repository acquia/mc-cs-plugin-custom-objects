<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
use Mautic\LeadBundle\Segment\OperatorOptions;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;

class ContactFilterMatcher
{
    use MatchFilterForLeadTrait;

    private CustomFieldModel $customFieldModel;
    private CustomObjectModel $customObjectModel;
    private CustomItemModel $customItemModel;
    private int $leadCustomItemFetchLimit;

    public function __construct(
        CustomFieldModel $customFieldModel,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        LeadListRepository $segmentRepository,
        int $leadCustomItemFetchLimit
    ) {
        $this->customFieldModel         = $customFieldModel;
        $this->customObjectModel        = $customObjectModel;
        $this->customItemModel          = $customItemModel;
        $this->segmentRepository        = $segmentRepository;
        $this->leadCustomItemFetchLimit = $leadCustomItemFetchLimit;
    }

    /**
     * @param mixed[] $filters
     * @param mixed[] $lead
     */
    public function match(array $filters, array $lead, bool &$hasCustomFields = false): bool
    {
        $customFieldValues = $this->getCustomFieldDataForLead($filters, (string) $lead['id']);

        if (!$customFieldValues) {
            return false;
        }

        $hasCustomFields = true;
        $lead            = array_merge($lead, $customFieldValues);

        return $this->matchFilterForLeadInCustomObject($filters, $lead);
    }

    /**
     * @param mixed[] $filters
     *
     * @return mixed[]
     */
    private function getCustomFieldDataForLead(array $filters, string $leadId): array
    {
        $customFieldValues = $cachedCustomItems = [];

        foreach ($filters as $condition) {
            try {
                if ('custom_object' !== $condition['object']) {
                    continue;
                }

                if ('cmf_' === substr($condition['field'], 0, 4)) {
                    $customField  = $this->customFieldModel->fetchEntity(
                        (int) explode('cmf_', $condition['field'])[1]
                    );
                    $customObject = $customField->getCustomObject();
                    $fieldAlias   = $customField->getAlias();
                } elseif ('cmo_' === substr($condition['field'], 0, 4)) {
                    $customObject = $this->customObjectModel->fetchEntity(
                        (int) explode('cmo_', $condition['field'])[1]
                    );
                    $fieldAlias   = 'name';
                } else {
                    continue;
                }

                $key = $customObject->getId().'-'.$leadId;
                if (!isset($cachedCustomItems[$key])) {
                    $cachedCustomItems[$key] = $this->getCustomItems($customObject, $leadId);
                }

                $result = $this->getCustomFieldValue($customObject, $fieldAlias, $cachedCustomItems[$key]);

                $customFieldValues[$condition['field']] = $result;
            } catch (NotFoundException|InvalidCustomObjectFormatListException $e) {
                continue;
            }
        }

        return $customFieldValues;
    }

    /**
     * @param mixed[] $customItems
     *
     * @return mixed[]
     */
    private function getCustomFieldValue(
        CustomObject $customObject,
        string $customFieldAlias,
        array $customItems
    ): array {
        $fieldValues = [];

        foreach ($customItems as $customItemData) {
            // Name is known from the CI data array.
            if ('name' === $customFieldAlias) {
                $fieldValues[] = $customItemData['name'];

                continue;
            }

            // Custom Field values are handled like this.
            $customItem = new CustomItem($customObject);
            $customItem->populateFromArray($customItemData);
            $customItem = $this->customItemModel->populateCustomFields($customItem);

            try {
                $fieldValue = $customItem->findCustomFieldValueForFieldAlias($customFieldAlias);
                // If the CO item doesn't have a value, get the default value
                if (empty($fieldValue->getValue())) {
                    $fieldValue->setValue($fieldValue->getCustomField()->getDefaultValue());
                }

                if (in_array($fieldValue->getCustomField()->getType(), ['multiselect', 'select'])) {
                    $fieldValues[] = $fieldValue->getValue();
                } else {
                    $fieldValues[] = $fieldValue->getCustomField()->getTypeObject()->valueToString($fieldValue);
                }
            } catch (NotFoundException $e) {
                // Custom field not found.
            }
        }

        return $fieldValues;
    }

    /**
     * @return array<mixed>
     */
    private function getCustomItems(CustomObject $customObject, string $leadId): array
    {
        $orderBy  = CustomItem::TABLE_ALIAS.'.id';
        $orderDir = 'DESC';

        $tableConfig = new TableConfig($this->leadCustomItemFetchLimit, 1, $orderBy, $orderDir);
        $tableConfig->addParameter('customObjectId', $customObject->getId());
        $tableConfig->addParameter('filterEntityType', 'contact');
        $tableConfig->addParameter('filterEntityId', $leadId);

        return $this->customItemModel->getArrayTableData($tableConfig);
    }

    /**
     * We have a similar function in MatchFilterForLeadTrait since we are unable to alter anything in Mautic 4.4,
     * hence there is some duplication of code.
     *
     * @param mixed[] $filter
     * @param mixed[] $lead
     */
    protected function matchFilterForLeadInCustomObject(array $filter, array $lead): bool
    {
        if (empty($lead['id'])) {
            // Lead in generated for preview with faked data
            return false;
        }

        $groups   = [];
        $groupNum = 0;

        foreach ($filter as $data) {
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

            if ('leadlist' === $data['type']) {
                $groups[$groupNum] = $this->isContactSegmentRelationshipValid(
                    (int) $lead['id'], $data['operator'], $data['filter']
                );
            }

            if (!array_key_exists($data['field'], $lead)) {
                continue;
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
}
