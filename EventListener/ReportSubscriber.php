<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCompany;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Report\ReportColumnsBuilder;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    const CUSTOM_OBJECTS_CONTEXT_GROUP = 'custom.objects';

    const CUSTOM_ITEM_TABLE_ALIAS        = 'ci';
    const CUSTOM_ITEM_XREF_CONTACT_ALIAS = 'cil';
    const CUSTOM_ITEM_XREF_COMPANY_ALIAS = 'cic';
    const LEADS_TABLE_ALIAS              = 'l';
    const USERS_TABLE_ALIAS              = 'u';
    const COMPANIES_TABLE_ALIAS          = 'comp';

    /**
     * @var array
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

    public function __construct(CustomObjectRepository $customObjectRepository, FieldsBuilder $fieldsBuilder, CompanyReportData $companyReportData)
    {
        $this->customObjectRepository = $customObjectRepository;
        $this->fieldsBuilder          = $fieldsBuilder;
        $this->companyReportData      = $companyReportData;
    }

    private function getCustomObjects(): ArrayCollection
    {
        if (null !== $this->customObjects) {
            return $this->customObjects;
        }

        $allCustomObjects = new ArrayCollection($this->customObjectRepository->findAll());
        $parentCustomObjects = $allCustomObjects->filter(function(CustomObject $customObject): bool {
            return CustomObject::TYPE_MASTER === $customObject->getType();
        });
        $parentCustomObjects = $this->sortCustomObjects($parentCustomObjects);

        $this->customObjects = array();

        foreach ($parentCustomObjects as $parentCustomObject) {
            $this->customObjects[] = $parentCustomObject;
            $childCustomObjects = $allCustomObjects->filter(function (CustomObject $childCustomObject) use ($parentCustomObject) : bool {
                return $childCustomObject->getMasterObject() ?
                    $parentCustomObject->getId() === $childCustomObject->getMasterObject()->getId()
                    :
                    false;
            });

            if (1 > $childCustomObjects->count()) {
                continue;
            }

            $this->customObjects = new ArrayCollection(array_merge($this->customObjects, $this->sortCustomObjects($childCustomObjects)->toArray()));
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
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    private function getContext(CustomObject $customObject): string
    {
        return static::CUSTOM_OBJECTS_CONTEXT_GROUP.'.'.$customObject->getId();
    }

    private function getContexts(): array
    {
        return $this->getCustomObjects()
            ->map([$this, 'getContext'])
            ->toArray();
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext($this->getContexts())) {
            return;
        }

        $columns = array_merge(
            $this->getLeadColumns(),
            $this->getCompanyColumns(),
            $event->getStandardColumns(static::CUSTOM_ITEM_TABLE_ALIAS.'.', ['description', 'publish_up', 'publish_down'])
        );

        /** @var CustomObject $customObject */
        foreach ($this->getCustomObjects() as $customObject) {
            $event->addTable(
                $this->getContext($customObject),
                [
                    'display_name' => $customObject->getNamePlural(),
                    'columns'      => array_merge($columns, (new ReportColumnsBuilder($customObject))->getColumns()),
                ],
                static::CUSTOM_OBJECTS_CONTEXT_GROUP
            );
        }
    }

    private function getLeadColumns(): array
    {
        return $this->fieldsBuilder->getLeadFieldsColumns(static::LEADS_TABLE_ALIAS.'.');
    }

    private function getCompanyColumns(): array
    {
        $companyColumns = $this->companyReportData->getCompanyData();
        // We don't need this column because we fetch company/lead relationships via custom objects
        unset($companyColumns['companies_lead.is_primary']);

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
        $contactsJoinCondition = sprintf('%s.id = %s.custom_item_id', static::CUSTOM_ITEM_TABLE_ALIAS, static::CUSTOM_ITEM_XREF_CONTACT_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_TABLE_ALIAS, $this->addTablePrefix(CustomItemXrefContact::TABLE_NAME), static::CUSTOM_ITEM_XREF_CONTACT_ALIAS, $contactsJoinCondition);
        $contactsTableJoinCondition = sprintf('%s.contact_id = %s.id', static::CUSTOM_ITEM_XREF_CONTACT_ALIAS, static::LEADS_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_XREF_CONTACT_ALIAS, $this->addTablePrefix('leads'), static::LEADS_TABLE_ALIAS, $contactsTableJoinCondition);
    }

    private function joinUsersColumns(QueryBuilder $queryBuilder): void
    {
        $usersJoinCondition = sprintf('%s.id = %s.owner_id', static::USERS_TABLE_ALIAS, static::LEADS_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::LEADS_TABLE_ALIAS, $this->addTablePrefix('users'), static::USERS_TABLE_ALIAS, $usersJoinCondition);
    }

    public function joinCompanyColumns(QueryBuilder $queryBuilder): void
    {
        // Joining companies tables
        $companiesJoinCondition = sprintf('%s.id = %s.custom_item_id', static::CUSTOM_ITEM_TABLE_ALIAS, static::CUSTOM_ITEM_XREF_COMPANY_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_TABLE_ALIAS, $this->addTablePrefix(CustomItemXrefCompany::TABLE_NAME), static::CUSTOM_ITEM_XREF_COMPANY_ALIAS, $companiesJoinCondition);
        $companiesTableJoinCondition = sprintf('%s.company_id = %s.id', static::CUSTOM_ITEM_XREF_COMPANY_ALIAS, static::COMPANIES_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_XREF_COMPANY_ALIAS, $this->addTablePrefix('companies'), static::COMPANIES_TABLE_ALIAS, $companiesTableJoinCondition);
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
        $queryBuilder->andWhere(static::CUSTOM_ITEM_TABLE_ALIAS.'.custom_object_id = :customObjectId');
        $queryBuilder->setParameter('customObjectId', $customObject->getId(), ParameterType::INTEGER);

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
    }
}
