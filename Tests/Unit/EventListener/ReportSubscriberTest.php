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

use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\ReportEvents;
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

    protected function setUp(): void
    {
        $this->customObjectRepository = $this->createMock(CustomObjectRepository::class);
        $this->fieldsBuilder          = $this->createMock(FieldsBuilder::class);
        $this->companyReportData      = $this->createMock(CompanyReportData::class);

        $this->reportSubscriber = new ReportSubscriber($this->customObjectRepository, $this->fieldsBuilder, $this->companyReportData);
    }

    public function testThatEventListenersAreSpecified()
    {
        $events = ReportSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(ReportEvents::REPORT_ON_BUILD, $events);
        $this->assertArrayHasKey(ReportEvents::REPORT_ON_GENERATE, $events);
        $this->assertContains('onReportBuilder', $events[ReportEvents::REPORT_ON_BUILD]);
        $this->assertContains('onReportGenerate', $events[ReportEvents::REPORT_ON_GENERATE]);
    }
}
