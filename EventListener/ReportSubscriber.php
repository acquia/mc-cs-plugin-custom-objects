<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ColumnCollectEvent;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\ReportEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Report\ReportColumnsBuilder;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    public const CUSTOM_OBJECTS_CONTEXT_GROUP    = 'custom.objects';
    public const CHILD_CUSTOM_OBJECT_NAME_PREFIX = '&nbsp;&nbsp;&nbsp;&nbsp;';

    public const CUSTOM_ITEM_TABLE_ALIAS                  = 'ci';
    public const PARENT_CUSTOM_ITEM_TABLE_ALIAS           = 'pci';
    public const CUSTOM_ITEM_XREF_CUSTOM_ITEM_TABLE_ALIAS = 'cixci';
    public const CUSTOM_ITEM_XREF_CUSTOM_ITEM_TABLE_NAME  = 'custom_item_xref_custom_item';
    public const CUSTOM_ITEM_XREF_CONTACT_TABLE_ALIAS     = 'cil';
    public const CONTACTS_COMPANIES_XREF                  = 'cic';
    public const LEADS_TABLE_ALIAS                        = 'l';
    public const USERS_TABLE_ALIAS                        = 'u';
    public const COMPANIES_TABLE_ALIAS                    = 'comp';

    /**
     * @var ArrayCollection
     */
    private $customObjects;

    /**
     * @var CustomObjectRepository
     */
    private $customObjectRepository;

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @var CompanyReportData
     */
    private $companyReportData;

    /**
     * @var ReportHelper
     */
    private $reportHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private FieldModel $fieldModel;

    public function __construct(
        CustomObjectRepository $customObjectRepository,
        FieldsBuilder $fieldsBuilder,
        CompanyReportData $companyReportData,
        ReportHelper $reportHelper,
        TranslatorInterface $translator,
        FieldModel $fieldModel
    ) {
        $this->customObjectRepository = $customObjectRepository;
        $this->fieldsBuilder          = $fieldsBuilder;
        $this->companyReportData      = $companyReportData;
        $this->reportHelper           = $reportHelper;
        $this->translator             = $translator;
        $this->fieldModel             = $fieldModel;
    }

    private function getCustomObjects(): ArrayCollection
    {
        if ($this->customObjects instanceof ArrayCollection) {
            return $this->customObjects;
        }

        $allCustomObjects    = new ArrayCollection($this->customObjectRepository->findAll());
        $parentCustomObjects = $allCustomObjects->filter(function (CustomObject $customObject): bool {
            return CustomObject::TYPE_MASTER === $customObject->getType();
        });
        $parentCustomObjects = $this->sortCustomObjects($parentCustomObjects);

        $this->customObjects = new ArrayCollection();
        foreach ($parentCustomObjects as $parentCustomObject) {
            $this->customObjects->add($parentCustomObject);
            $childCustomObject = $allCustomObjects->filter(function (CustomObject $childCustomObject) use ($parentCustomObject): bool {
                return $childCustomObject->getMasterObject() ?
                    $parentCustomObject->getId() === $childCustomObject->getMasterObject()->getId()
                    :
                    false;
            })->first();

            if (!$childCustomObject) {
                continue;
            }

            $childCustomObject->setNamePlural(self::CHILD_CUSTOM_OBJECT_NAME_PREFIX.$childCustomObject->getNamePlural());
            $this->customObjects->add($childCustomObject);
        }

        return $this->customObjects;
    }

    private function sortCustomObjects(ArrayCollection $customObjects): ArrayCollection
    {
        $customObjects = $customObjects->toArray();
        usort($customObjects, function (CustomObject $a, CustomObject $b): int {
            return strnatcmp($a->getNamePlural(), $b->getNamePlural());
        });

        return new ArrayCollection($customObjects);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD           => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_COLUMN_COLLECT  => ['onReportColumnCollect', 0],
            ReportEvents::REPORT_ON_GENERATE        => [
                ['onReportGenerate', 0],
                ['onFormResultReportGenerate', -1],
            ],
        ];
    }

    private function getContext(CustomObject $customObject): string
    {
        return static::CUSTOM_OBJECTS_CONTEXT_GROUP.'.'.$customObject->getId();
    }

    private function getContexts(): array
    {
        return $this->getCustomObjects()
            ->map(function (CustomObject $customObject) {
                return $this->getContext($customObject);
            })
            ->toArray();
    }

    private function getStandardColumns(string $prefix)
    {
        return $this->reportHelper->getStandardColumns($prefix, ['description', 'publish_up', 'publish_down']);
    }

    private function addPrefixToColumnLabel(array $columns, string $prefix): array
    {
        foreach ($columns as $alias => $column) {
            $columnLabel = $this->translator->trans($columns[$alias]['label']);
            if (0 === strpos($columnLabel, $prefix)) {
                $columns[$alias]['label'] = $columnLabel;
                // Don't add the prefix twice
                continue;
            }

            $columns[$alias]['label'] = sprintf(
                '%s %s',
                str_replace(self::CHILD_CUSTOM_OBJECT_NAME_PREFIX, '', $prefix),
                $columnLabel
            );
        }

        return $columns;
    }

    private function getCustomObjectColumns(CustomObject $customObject, string $customItemTablePrefix): array
    {
        $standardColumns     = $this->getStandardColumns($customItemTablePrefix);
        $customFieldsColumns = (new ReportColumnsBuilder($customObject))->getColumns();

        return array_merge($standardColumns, $customFieldsColumns);
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext($this->getContexts())) {
            return;
        }

        $companyColumns = $this->getCompanyColumns();
        $contactColumns = $this->addPrefixToColumnLabel(
            $this->getLeadColumns(),
            $this->translator->trans('mautic.lead.contact')
        );

        /** @var CustomObject $customObject */
        foreach ($this->getCustomObjects() as $customObject) {
            $customObjectColumns       = $this->getCustomObjectColumns($customObject, static::CUSTOM_ITEM_TABLE_ALIAS.'.');
            $parentCustomObjectColumns = [];

            if ($customObject->getMasterObject()) {
                $parentCustomObjectColumns = $this->addPrefixToColumnLabel(
                    $this->getCustomObjectColumns(
                        $customObject->getMasterObject(),
                        static::PARENT_CUSTOM_ITEM_TABLE_ALIAS.'.'
                    ),
                    $customObject->getMasterObject()->getNameSingular()
                );
            }

            $columns = array_merge(
                $this->addPrefixToColumnLabel($customObjectColumns, $customObject->getNameSingular()),
                $contactColumns,
                $companyColumns,
                $parentCustomObjectColumns
            );

            $event->addTable(
                $this->getContext($customObject),
                [
                    'display_name' => $customObject->getNamePlural(),
                    'columns'      => $columns,
                ],
                static::CUSTOM_OBJECTS_CONTEXT_GROUP
            );
        }
    }

    public function onReportColumnCollect(ColumnCollectEvent $event): void
    {
        $object = $event->getObject();

        if (!$this->customObjectRepository->checkAliasExists($object)) {
            return;
        }

        $customObject        = $this->customObjectRepository->findOneBy(['alias' => $object]);
        $properties          = $event->getProperties();
        $customItemTableAlias = static::CUSTOM_ITEM_TABLE_ALIAS.'_'.$customObject->getId();
        $customObjectColumns  = $this->getCustomObjectColumns($customObject, $customItemTableAlias.'.');

        array_walk(
            $customObjectColumns,
            function (&$item, $index) use ($customObject, $properties) {
                $item['idCustomObject'] = $customObject->getId();
                $item                   = array_merge($item, $properties);
            }
        );

        $columns = array_merge(
            $columns ?? [],
            $this->addPrefixToColumnLabel($customObjectColumns, $customObject->getNameSingular()),
            $parentCustomObjectColumns ?? []
        );

        $event->addColumns($columns);
    }

    private function getLeadColumns(): array
    {
        return $this->fieldsBuilder->getLeadFieldsColumns(static::LEADS_TABLE_ALIAS.'.');
    }

    private function getCompanyColumns(): array
    {
        $companyColumns = $this->companyReportData->getCompanyData();
        // We don't need this column because we fetch company/lead relationships via custom objects
        unset($companyColumns['companies_lead.is_primary'], $companyColumns['companies_lead.date_added']);

        return $companyColumns;
    }

    private function addTablePrefix(string $table): string
    {
        return MAUTIC_TABLE_PREFIX.$table;
    }

    private function initializeCustomObject(string $context): CustomObject
    {
        $customObjectId = explode('.', $context);
        $customObjectId = (int) end($customObjectId);

        if (1 > $customObjectId) {
            throw new \Exception('Custom Object ID is not defined.');
        }

        /** @var CustomObject $customObject */
        $customObject = $this->customObjectRepository->find($customObjectId);
        if (!($customObject instanceof CustomObject)) {
            throw new \Exception('Custom Object doesn\'t exist');
        }

        return $customObject;
    }

    private function joinLeadColumns(QueryBuilder $queryBuilder): void
    {
        // Joining contacts tables
        $contactsJoinCondition = sprintf('%s.id = %s.custom_item_id', static::CUSTOM_ITEM_TABLE_ALIAS, static::CUSTOM_ITEM_XREF_CONTACT_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_TABLE_ALIAS, $this->addTablePrefix(CustomItemXrefContact::TABLE_NAME), static::CUSTOM_ITEM_XREF_CONTACT_TABLE_ALIAS, $contactsJoinCondition);
        $contactsTableJoinCondition = sprintf('%s.contact_id = %s.id', static::CUSTOM_ITEM_XREF_CONTACT_TABLE_ALIAS, static::LEADS_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_XREF_CONTACT_TABLE_ALIAS, $this->addTablePrefix('leads'), static::LEADS_TABLE_ALIAS, $contactsTableJoinCondition);
    }

    private function joinUsersColumns(QueryBuilder $queryBuilder): void
    {
        $usersJoinCondition = sprintf('%s.id = %s.owner_id', static::USERS_TABLE_ALIAS, static::LEADS_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::LEADS_TABLE_ALIAS, $this->addTablePrefix('users'), static::USERS_TABLE_ALIAS, $usersJoinCondition);
    }

    private function joinCompanyColumns(QueryBuilder $queryBuilder): void
    {
        // Joining companies tables
        $companiesJoinCondition = sprintf('%s.id = %s.lead_id', static::LEADS_TABLE_ALIAS, static::CONTACTS_COMPANIES_XREF);
        $queryBuilder->leftJoin(static::LEADS_TABLE_ALIAS, $this->addTablePrefix('companies_leads'), static::CONTACTS_COMPANIES_XREF, $companiesJoinCondition);
        $companiesTableJoinCondition = sprintf('%s.company_id = %s.id', static::CONTACTS_COMPANIES_XREF, static::COMPANIES_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CONTACTS_COMPANIES_XREF, $this->addTablePrefix('companies'), static::COMPANIES_TABLE_ALIAS, $companiesTableJoinCondition);
    }

    private function joinParentCustomObjectStandardColumns(QueryBuilder $queryBuilder): void
    {
        // Joining companies tables
        $relationshipTableJoinCondition = sprintf('%s.id = %s.custom_item_id_higher', static::CUSTOM_ITEM_TABLE_ALIAS, self::CUSTOM_ITEM_XREF_CUSTOM_ITEM_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_TABLE_ALIAS, $this->addTablePrefix(self::CUSTOM_ITEM_XREF_CUSTOM_ITEM_TABLE_NAME), static::CUSTOM_ITEM_XREF_CUSTOM_ITEM_TABLE_ALIAS, $relationshipTableJoinCondition);
        $parentCustomItemTableJoinCondition = sprintf('%s.custom_item_id_lower = %s.id', static::CUSTOM_ITEM_XREF_CUSTOM_ITEM_TABLE_ALIAS, static::PARENT_CUSTOM_ITEM_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_XREF_CUSTOM_ITEM_TABLE_ALIAS, $this->addTablePrefix(CustomItem::TABLE_NAME), static::PARENT_CUSTOM_ITEM_TABLE_ALIAS, $parentCustomItemTableJoinCondition);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext($this->getContexts())) {
            return;
        }

        $customObject = $this->initializeCustomObject($event->getContext());

        $queryBuilder = $event->getQueryBuilder();
        $queryBuilder->from($this->addTablePrefix(CustomItem::TABLE_NAME), static::CUSTOM_ITEM_TABLE_ALIAS);
        $queryBuilder->andWhere($queryBuilder->expr()->eq(static::CUSTOM_ITEM_TABLE_ALIAS.'.custom_object_id', $customObject->getId()));

        $event->applyDateFilters($queryBuilder, 'date_added', static::CUSTOM_ITEM_TABLE_ALIAS);

        $userColumns = [
            static::USERS_TABLE_ALIAS.'.first_name',
            static::USERS_TABLE_ALIAS.'.last_name',
        ];

        $usesLeadsColumns    = $event->usesColumn(array_keys($this->getLeadColumns()));
        $usesUserColumns     = $event->usesColumn($userColumns);
        $usesIpAddressColumn = $event->usesColumn('i.ip_address');

        if ($usesLeadsColumns || $usesUserColumns || $usesIpAddressColumn) {
            $this->joinLeadColumns($queryBuilder);
        }

        if ($usesUserColumns) {
            $this->joinUsersColumns($queryBuilder);
        }

        if ($usesIpAddressColumn) {
            $event->addLeadIpAddressLeftJoin($queryBuilder);
        }

        if ($this->companyReportData->eventHasCompanyColumns($event)) {
            $this->joinCompanyColumns($queryBuilder);
        }

        // Join custom objects tables
        $reportColumnsBuilder = new ReportColumnsBuilder($customObject);
        $reportColumnsBuilder->setFilterColumnsCallback([$event, 'usesColumn']);
        $reportColumnsBuilder->joinReportColumns($queryBuilder, static::CUSTOM_ITEM_TABLE_ALIAS);

        $parentCustomObject = $customObject->getMasterObject();
        if (!($parentCustomObject instanceof CustomObject)) {
            return;
        }

        $standardParentCustomObjectColumns = $this->getStandardColumns(static::PARENT_CUSTOM_ITEM_TABLE_ALIAS.'.');
        $parentCustomObjectColumns         = (new ReportColumnsBuilder($customObject->getMasterObject()))->getColumns();

        $parentCustomObjectColumns = array_keys(array_merge($parentCustomObjectColumns, $standardParentCustomObjectColumns));

        $usesParentCustomObjectsColumns = false;
        foreach ($parentCustomObjectColumns as $column) {
            if ($event->usesColumn($column)) {
                $usesParentCustomObjectsColumns = true;
                break;
            }
        }

        if (!$usesParentCustomObjectsColumns) {
            // No need to join parent custom object columns if we don't use them
            return;
        }

        $this->joinParentCustomObjectStandardColumns($queryBuilder);

        // Join parent custom objects tables
        $parentCustomObjectReportColumnsBuilder = new ReportColumnsBuilder($parentCustomObject);
        $parentCustomObjectReportColumnsBuilder->setFilterColumnsCallback([$event, 'usesColumn']);
        $parentCustomObjectReportColumnsBuilder->joinReportColumns($queryBuilder, static::PARENT_CUSTOM_ITEM_TABLE_ALIAS);
    }

    public function onFormResultReportGenerate(ReportGeneratorEvent $event): void
    {
        $contextFormResult     = 'form.results';
        $prefixFormResultTable = 'fr';
        $context               = $event->getContext();

        if (!str_starts_with($context, $contextFormResult)) {
            return;
        }

        $addedCustomObjects = [];
        $columns            = array_filter(
            $event->getOptions()['columns'],
            function ($elem) use (&$addedCustomObjects) {
                // left one column for each custom object
                $addToColumnList    = key_exists('idCustomObject', $elem) && !in_array($elem['idCustomObject'], $addedCustomObjects);
                $addedCustomObjects[] = $elem['idCustomObject'] ?? '';

                return $addToColumnList;
            }
        );

        $queryBuilder = $event->getQueryBuilder();

        foreach ($columns as $column) {
            $customObject          = $this->customObjectRepository->find($column['idCustomObject']);
            $field                 = $this->fieldModel->getEntity($column['idFormField']);

            $customItemTableAlias  = static::CUSTOM_ITEM_TABLE_ALIAS.'_'.$customObject->getId();

            $colCustomObjectName   = sprintf('`%s`.`id`', $customItemTableAlias);
            $colMappedField        = sprintf('`%s`.`%s`', $prefixFormResultTable, $field->getAlias());
            $colCustomItemObjectId = sprintf('`%s`.`custom_object_id`', $customItemTableAlias);
            $colCustomObjectId     = sprintf('%s', $customObject->getId());

            $joinCondition         = $this->fieldModel->hasChoices($field)
                ? "{$colMappedField} LIKE CONCAT('%', {$colCustomObjectName}, '%') AND {$colCustomItemObjectId} = {$colCustomObjectId}"
                : "{$colMappedField} = {$colCustomObjectName} AND {$colCustomItemObjectId} = {$colCustomObjectId}";
            $queryBuilder->leftJoin($prefixFormResultTable, CustomItem::TABLE_NAME, $customItemTableAlias, $joinCondition);

            $addedCustomObjects[]  = $column['idCustomObject'];
            $reportColumnsBuilder  = new ReportColumnsBuilder($customObject);
            $reportColumnsBuilder->setFilterColumnsCallback([$event, 'usesColumn']);
            $reportColumnsBuilder->joinReportColumns($queryBuilder, $customItemTableAlias);
        }
    }
}
