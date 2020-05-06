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
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\ReportEvents;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\EventListener\ReportSubscriber;
use MauticPlugin\CustomObjectsBundle\Repository\CustomObjectRepository;
use PHPUnit\Framework\TestCase;

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

    protected function setUp(): void
    {
        $this->customObjectRepository = $this->createMock(CustomObjectRepository::class);
        $this->fieldsBuilder          = $this->createMock(FieldsBuilder::class);
        $this->companyReportData      = $this->createMock(CompanyReportData::class);
        $this->reportSubscriber = new ReportSubscriber($this->customObjectRepository, $this->fieldsBuilder, $this->companyReportData);

        $this->reportBuilderEvent = $this->createMock(ReportBuilderEvent::class);
    }

    private function getCustomFieldsCollection(): ArrayCollection
    {
        $label1       = uniqid();
        $customField1 = new CustomField();
        $customField1->setId(1);
        $customField1->setLabel($label1);
        $typeObject1 = new TextType($this->translatorInterface, $this->filterOperatorProviderInterface);
        $customField1->setTypeObject($typeObject1);
        $customField1->setType($typeObject1->getKey());

        $label2       = uniqid();
        $customField2 = new CustomField();
        $customField2->setId(2);
        $customField2->setLabel($label2);
        $typeObject2 = new MultiselectType($this->translatorInterface, $this->filterOperatorProviderInterface, $this->csvHelper);
        $customField2->setTypeObject($typeObject2);
        $customField2->setType($typeObject2->getKey());

        $label3       = uniqid();
        $customField3 = new CustomField();
        $customField3->setId(3);
        $customField3->setLabel($label3);
        $typeObject3 = new DateTimeType($this->translatorInterface, $this->filterOperatorProviderInterface);
        $customField3->setTypeObject($typeObject3);
        $customField3->setType($typeObject3->getKey());

        return [
            $customField1,
            $customField2,
            $customField3,
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
        $this->reportSubscriber->onReportBuilder($this->reportBuilderEvent);
    }
}
