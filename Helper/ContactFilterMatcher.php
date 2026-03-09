<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Helper;

use Doctrine\DBAL\Connection;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\LeadListRepository;
use MauticPlugin\CustomObjectsBundle\DTO\TableConfig;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use MauticPlugin\CustomObjectsBundle\Polyfill\EventListener\MatchFilterForLeadTrait as MatchFilterForLeadTraitPolyfill;

if (method_exists(MatchFilterForLeadTrait::class, 'transformFilterDataForLead')) {
    class_alias(MatchFilterForLeadTrait::class, '\MauticPlugin\CustomObjectsBundle\Helper\MatchFilterForLeadTraitAlias');
} else {
    class_alias(MatchFilterForLeadTraitPolyfill::class, '\MauticPlugin\CustomObjectsBundle\Helper\MatchFilterForLeadTraitAlias');
}

class ContactFilterMatcher
{
    use MatchFilterForLeadTraitAlias {
        transformFilterDataForLead as transformFilterDataForLeadAlias;
    }

    private CustomFieldModel $customFieldModel;
    private CustomObjectModel $customObjectModel;
    private CustomItemModel $customItemModel;
    private CompanyRepository $companyRepository;
    private Connection $connection;
    private int $leadCustomItemFetchLimit;

    public function __construct(
        CustomFieldModel $customFieldModel,
        CustomObjectModel $customObjectModel,
        CustomItemModel $customItemModel,
        LeadListRepository $segmentRepository,
        CompanyRepository $companyRepository,
        Connection $connection,
        int $leadCustomItemFetchLimit
    ) {
        $this->customFieldModel         = $customFieldModel;
        $this->customObjectModel        = $customObjectModel;
        $this->customItemModel          = $customItemModel;
        $this->segmentRepository        = $segmentRepository;
        $this->companyRepository        = $companyRepository;
        $this->connection               = $connection;
        $this->leadCustomItemFetchLimit = $leadCustomItemFetchLimit;
    }

    /**
     * @param mixed[] $filters
     * @param mixed[] $lead
     */
    public function match(array $filters, array $lead, bool &$hasCustomFields = false): bool
    {
        $leadId            = (string) $lead['id'];
        $customFieldValues = $this->getCustomFieldDataForLead($filters, $leadId);

        if (!$customFieldValues) {
            return false;
        }

        $hasCustomFields = true;
        $lead            = array_merge($lead, $customFieldValues);

        if (!isset($lead['companies']) && $this->doFiltersContainCompanyFilter($filters)) {
            $lead['companies'] = $this->companyRepository->getCompaniesByLeadId($leadId);
        }

        if (!isset($lead['tags']) && $this->doFiltersContainTagsFilter($filters)) {
            $lead['tags'] = $this->getTagIdsByLeadId($leadId);
        }

        return $this->matchFilterForLead($filters, $lead);
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
     * @param mixed[] $data
     * @param mixed[] $lead
     *
     * @return ?mixed[]
     */
    private function transformFilterDataForLead(array $data, array $lead): ?array
    {
        if ('custom_object' === $data['object']) {
            return $lead[$data['field']];
        }

        return $this->transformFilterDataForLeadAlias($data, $lead);
    }

    /**
     * @return string[]
     */
    public function getTagIdsByLeadId(string $leadId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('tag_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x')
            ->where('x.lead_id = :leadId')
            ->setParameter('leadId', $leadId)
            ->execute()
            ->fetchFirstColumn();
    }
}
