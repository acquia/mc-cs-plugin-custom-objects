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

    protected function setUp(CustomObjectRepository $customObjectRepository, FieldsBuilder $fieldsBuilder, CompanyReportData $companyReportData): void
    {
        $this->customObjectRepository = $this->createMock(CustomObjectRepository::class);
        $this->fieldsBuilder          = $this->createMock(FieldsBuilder::class);
        $this->companyReportData      = $this->createMock(CompanyReportData::class);
    }

    public function testThatEventListenersAreSpecified()
    {

    }
}
