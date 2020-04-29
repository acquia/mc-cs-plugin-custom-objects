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

use Doctrine\DBAL\ParameterType;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefCompany;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Report\ColumnsBuilder;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    const CUSTOM_OBJECTS_CONTEXT_GROUP = 'custom.objects';

    const CUSTOM_ITEM_TABLE_ALIAS = 'ci';
    const CUSTOM_ITEM_XREF_CONTACT_ALIAS = 'cil';
    const CUSTOM_ITEM_XREF_COMPANY_ALIAS = 'cic';
    const LEADS_TABLE_ALIAS = 'l';
    const LEADS_TABLE_PREFIX = self::LEADS_TABLE_ALIAS . '.';
    const COMPANIES_TABLE_ALIAS = 'comp';

    private static $customObjects = null;

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
        $this->fieldsBuilder = $fieldsBuilder;
        $this->companyReportData = $companyReportData;
    }

    private function getCustomObjects(): array
    {
        if (null !== static::$customObjects) {
            return static::$customObjects;
        }

        static::$customObjects = $this->customObjectRepository->findAll();
        usort(static::$customObjects, function (CustomObject $a, CustomObject $b): int {
            return strnatcmp($a->getNamePlural(), $b->getNamePlural());
        });

        return static::$customObjects;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    private function getContext(CustomObject $customObject): string
    {
        return static::CUSTOM_OBJECTS_CONTEXT_GROUP . '.' . $customObject->getId();
    }

    public function getContexts(): array
    {
        return array_map([$this, 'getContext'], $this->getCustomObjects());
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext($this->getContexts())) {
            return;
        }

        $columns = array_merge(
            $this->fieldsBuilder->getLeadFieldsColumns(static::LEADS_TABLE_PREFIX),
            $this->companyReportData->getCompanyData(),
            $event->getStandardColumns(static::CUSTOM_ITEM_TABLE_ALIAS . '.', ['description', 'publish_up', 'publish_down'])
        );

        /** @var CustomObject $customObject */
        foreach ($this->getCustomObjects() as $customObject) {
            $event->addTable(
                $this->getContext($customObject),
                [
                    'display_name' => $customObject->getNamePlural(),
                    'columns' => array_merge($columns, (new ColumnsBuilder($customObject))->getColumns()),
                ],
                static::CUSTOM_OBJECTS_CONTEXT_GROUP
            );
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext($this->getContexts())) {
            return;
        }

        $customObjectId = (int)preg_replace('/[^\d]/', '', $event->getContext());
        if (1 > $customObjectId) {
            throw new \RuntimeException('Custom Object ID is not defined.');
        }

        /** @var CustomObject $customObject */
        $customObject = $this->customObjectRepository->find($customObjectId);

        $queryBuilder = $event->getQueryBuilder();
        $queryBuilder
            ->from(MAUTIC_TABLE_PREFIX . CustomItem::TABLE_NAME, static::CUSTOM_ITEM_TABLE_ALIAS)
            ->andWhere(static::CUSTOM_ITEM_TABLE_ALIAS . '.custom_object_id = :customObjectId')
            ->setParameter('customObjectId', $customObject->getId(), ParameterType::INTEGER);

        // Joining contacts tables
        $contactsJoinCondition = sprintf('%s.id = %s.custom_item_id', static::CUSTOM_ITEM_TABLE_ALIAS, static::CUSTOM_ITEM_XREF_CONTACT_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_TABLE_ALIAS, MAUTIC_TABLE_PREFIX . CustomItemXrefContact::TABLE_NAME, static::CUSTOM_ITEM_XREF_CONTACT_ALIAS, $contactsJoinCondition);
        $contactsTableJoinCondition = sprintf('%s.contact_id = %s.id', static::CUSTOM_ITEM_XREF_CONTACT_ALIAS, static::LEADS_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_XREF_CONTACT_ALIAS, MAUTIC_TABLE_PREFIX . 'leads', static::LEADS_TABLE_ALIAS, $contactsTableJoinCondition);

        // Joining companies tables
        $companiesJoinCondition = sprintf('%s.id = %s.custom_item_id', static::CUSTOM_ITEM_TABLE_ALIAS, static::CUSTOM_ITEM_XREF_COMPANY_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_TABLE_ALIAS, MAUTIC_TABLE_PREFIX . CustomItemXrefCompany::TABLE_NAME, static::CUSTOM_ITEM_XREF_COMPANY_ALIAS, $companiesJoinCondition);
        $companiesTableJoinCondition = sprintf('%s.company_id = %s.id', static::CUSTOM_ITEM_XREF_COMPANY_ALIAS, static::COMPANIES_TABLE_ALIAS);
        $queryBuilder->leftJoin(static::CUSTOM_ITEM_XREF_COMPANY_ALIAS, MAUTIC_TABLE_PREFIX . 'companies', static::COMPANIES_TABLE_ALIAS, $companiesTableJoinCondition);

        // Join custom objects tables
        $columnsBuilder = new ColumnsBuilder($customObject);
        $callback = function(string $columnName) use ($event): bool {
            return $event->hasColumn($columnName);
        };

        $columnsBuilder
            ->setValidateColumnCallback($callback)
            ->prepareQuery($queryBuilder, static::CUSTOM_ITEM_TABLE_ALIAS);
    }
}
