<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Report;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\DateTimeType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\MultiselectType;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\TextType;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Helper\CsvHelper;
use MauticPlugin\CustomObjectsBundle\Report\ReportColumnsBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportColumnsBuilderTest extends TestCase
{
    /**
     * @var MockObject|CustomObject
     */
    private $customObject;

    /**
     * @var ReportColumnsBuilder
     */
    private $reportColumnsBuilder;

    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translatorInterface;

    /**
     * @var MockObject|FilterOperatorProviderInterface
     */
    private $filterOperatorProviderInterface;

    /**
     * @var MockObject|CsvHelper
     */
    private $csvHelper;

    /**
     * @var MockObject|Connection
     */
    private $connection;

    protected function setUp(): void
    {
        defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');

        $this->customObject                    = $this->createMock(CustomObject::class);
        $this->reportColumnsBuilder            = new ReportColumnsBuilder($this->customObject);
        $this->connection                      = $this->createMock(Connection::class);
        $this->queryBuilder                    = $this->createMock(QueryBuilder::class);
        $this->translatorInterface             = $this->createMock(TranslatorInterface::class);
        $this->filterOperatorProviderInterface = $this->createMock(FilterOperatorProviderInterface::class);
        $this->csvHelper                       = $this->createMock(CsvHelper::class);
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

        return new ArrayCollection([
            $customField1,
            $customField2,
            $customField3,
        ]);
    }

    public function testThatGetColumnsMethodReturnsCorrectColumns(): void
    {
        $collection = $this->getCustomFieldsCollection();

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn($collection);

        $columns = $this->reportColumnsBuilder->getColumns();

        $this->assertSame($columns, [
            '_c4ca4238.value' => [
                'label' => $collection->get(0)->getLabel(),
                'type'  => 'string',
            ],
            '_c81e728d.value' => [
                'label' => $collection->get(1)->getLabel(),
                'type'  => 'string',
            ],
            '_eccbc87e.value' => [
                'label' => $collection->get(2)->getLabel(),
                'type'  => 'datetime',
            ],
        ]);
    }

    public function callbackFunction(string $columnName): bool
    {
        return '_eccbc87e.value' != $columnName;
    }

    public function testThatOnReportGenerateMethodBuildsCorrectQuery(): void
    {
        $collection = $this->getCustomFieldsCollection();

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn($collection);

        $this->queryBuilder->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->queryBuilder->expects($this->exactly(3))
            ->method('leftJoin');

        $this->reportColumnsBuilder->joinReportColumns($this->queryBuilder, 'someAlias');
    }

    public function testThatCallbackMethodAllowsToControlWhatColumnsWillBeJoined(): void
    {
        $collection = $this->getCustomFieldsCollection();

        $this->customObject->expects($this->once())
            ->method('getCustomFields')
            ->willReturn($collection);

        $this->queryBuilder->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('leftJoin');

        $this->reportColumnsBuilder->setFilterColumnsCallback([$this, 'callbackFunction']);
        $this->reportColumnsBuilder->joinReportColumns($this->queryBuilder, 'someAlias');
    }
}
