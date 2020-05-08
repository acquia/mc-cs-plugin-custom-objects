<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\EventListener\ReportSubscriber;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

class ReportSubscriberTest extends TestCase
{
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
     * @var ReportSubscriber
     */
    private $reportSubscriber;

    /**
     * @var ReportBuilderEvent
     */
    private $reportBuilderEvent;

    /**
     * @var TranslatorInterface
     */
    private $translatorInterface;

    /**
     * @var FilterOperatorProviderInterface
     */
    private $filterOperatorProviderInterface;

    /**
     * @var CsvHelper
     */
    private $csvHelper;

    /**
     * @var ReportGeneratorEvent
     */
    private $reportGeneratorEvent;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp(): void
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $this->customObjectRepository = $this->createMock(CustomObjectRepository::class);
        $this->fieldsBuilder          = $this->createMock(FieldsBuilder::class);
        $this->companyReportData      = $this->createMock(CompanyReportData::class);
        $this->reportSubscriber = new ReportSubscriber($this->customObjectRepository, $this->fieldsBuilder, $this->companyReportData);
        $this->reportBuilderEvent = $this->createMock(ReportBuilderEvent::class);
        $this->translatorInterface             = $this->createMock(TranslatorInterface::class);
        $this->filterOperatorProviderInterface = $this->createMock(FilterOperatorProviderInterface::class);
        $this->csvHelper                       = $this->createMock(CsvHelper::class);
        $this->reportGeneratorEvent = $this->createMock(ReportGeneratorEvent::class);
        $this->queryBuilder                    = $this->createMock(QueryBuilder::class);
        $this->connection =     $this->createMock(Connection::class);
    }

    private function getCustomFieldsCollection(int $batch = 1): ArrayCollection
    {
        $label1       = uniqid();
        $customField1 = new CustomField();
        $customField1->setId(1 * $batch);
        $customField1->setLabel($label1);
        $typeObject1 = new TextType($this->translatorInterface, $this->filterOperatorProviderInterface);
        $customField1->setTypeObject($typeObject1);
        $customField1->setType($typeObject1->getKey());

        $label2       = uniqid();
        $customField2 = new CustomField();
        $customField2->setId(2 * $batch);
        $customField2->setLabel($label2);
        $typeObject2 = new MultiselectType($this->translatorInterface, $this->filterOperatorProviderInterface, $this->csvHelper);
        $customField2->setTypeObject($typeObject2);
        $customField2->setType($typeObject2->getKey());

        $label3       = uniqid();
        $customField3 = new CustomField();
        $customField3->setId(3 * $batch);
        $customField3->setLabel($label3);
        $typeObject3 = new DateTimeType($this->translatorInterface, $this->filterOperatorProviderInterface);
        $customField3->setTypeObject($typeObject3);
        $customField3->setType($typeObject3->getKey());

        return new ArrayCollection([
            $customField1,
            $customField2,
            $customField3,
        ]);
    }

    private function getCustomObjectsCollection(): array
    {
        $customObject1 = new CustomObject();
        $customObject1->setCustomFields($this->getCustomFieldsCollection());
        $customObject1->setNamePlural('Custom Objects #1');
        $customObject2 = new CustomObject();
        $customObject2->setCustomFields($this->getCustomFieldsCollection(2));
        $customObject2->setNamePlural('Custom Objects #2');
        return [
            $customObject1,
            $customObject2,
        ];
    }

    public function testThatEventListenersAreSpecified(): void
    {
        $events = ReportSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(ReportEvents::REPORT_ON_BUILD, $events);
        $this->assertArrayHasKey(ReportEvents::REPORT_ON_GENERATE, $events);
        $this->assertContains('onReportBuilder', $events[ReportEvents::REPORT_ON_BUILD]);
        $this->assertContains('onReportGenerate', $events[ReportEvents::REPORT_ON_GENERATE]);
    }

    public function testOnReportBuilderMethod(): void
    {
        $this->customObjectRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($this->getCustomObjectsCollection());

        $this->reportBuilderEvent->expects($this->once())
            ->method('checkContext')
            ->willReturn(true);

        $this->fieldsBuilder->expects($this->once())
            ->method('getLeadFieldsColumns')
            ->willReturn([]);

        $this->companyReportData->expects($this->once())
            ->method('getCompanyData')
            ->willReturn([]);

        $this->reportBuilderEvent->expects($this->once())
            ->method('getStandardColumns')
            ->willReturn([]);

        $this->reportBuilderEvent->expects($this->exactly(2))
            ->method('addTable');

        $this->reportSubscriber->onReportBuilder($this->reportBuilderEvent);
    }

    public function testThatWeDontProcessWrongContexts(): void
    {
        $this->customObjectRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($this->getCustomObjectsCollection());

        $this->reportBuilderEvent->expects($this->once())
            ->method('checkContext')
            ->willReturn(false);

        $this->fieldsBuilder->expects($this->never())
            ->method('getLeadFieldsColumns');

        $this->companyReportData->expects($this->never())
            ->method('getCompanyData');

        $this->reportBuilderEvent->expects($this->never())
            ->method('getStandardColumns');

        $this->reportBuilderEvent->expects($this->never())
            ->method('addTable');

        $this->reportSubscriber->onReportBuilder($this->reportBuilderEvent);
    }

    public function testOnReportGenerateMethod(): void
    {
        $customObjectsCollection = $this->getCustomObjectsCollection();

        $this->customObjectRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($customObjectsCollection);

        $this->reportGeneratorEvent->expects($this->once())
            ->method('checkContext')
            ->willReturn(true);

        $this->reportGeneratorEvent->expects($this->once())
            ->method('getContext')
            ->willReturn('custom.object.1');

        $this->customObjectRepository->expects($this->once())
            ->method('find')
            ->willReturn($customObjectsCollection[0]);

        $this->reportGeneratorEvent->expects($this->once())
            ->method('getContext')
            ->willReturn('custom.object.1');

        $this->reportGeneratorEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->fieldsBuilder->expects($this->once())
            ->method('getLeadFieldsColumns')
            ->willReturn([]);

        $this->companyReportData->expects($this->once())
            ->method('eventHasCompanyColumns')
            ->with($this->reportGeneratorEvent)
            ->willReturn(true);

        $this->reportGeneratorEvent->expects($this->exactly(6))
            ->method('usesColumn')
            ->willReturnOnConsecutiveCalls(true, true, true, true, true, false);

        $this->reportSubscriber->onReportGenerate($this->reportGeneratorEvent);
    }
}
