<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\LeadBundle\Entity\CompanyRepository;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;
use MauticPlugin\CustomObjectsBundle\Exception\NotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\ContactFilterMatcher;
use MauticPlugin\CustomObjectsBundle\Helper\FilterEvaluator;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomObjectModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactFilterMatcherTest extends TestCase
{
    /** @var CustomFieldModel&MockObject */
    private MockObject $customFieldModel;

    /** @var CustomObjectModel&MockObject */
    private MockObject $customObjectModel;

    /** @var CustomItemModel&MockObject */
    private MockObject $customItemModel;

    /** @var CompanyRepository&MockObject */
    private MockObject $companyRepository;

    /** @var Connection&MockObject */
    private MockObject $connection;

    private ContactFilterMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldModel  = $this->createMock(CustomFieldModel::class);
        $this->customObjectModel = $this->createMock(CustomObjectModel::class);
        $this->customItemModel   = $this->createMock(CustomItemModel::class);
        $this->companyRepository = $this->createMock(CompanyRepository::class);
        $this->connection        = $this->createMock(Connection::class);

        $this->matcher = new ContactFilterMatcher(
            $this->customFieldModel,
            $this->customObjectModel,
            $this->customItemModel,
            $this->companyRepository,
            $this->connection,
            new FilterEvaluator(),
            10
        );
    }

    public function testMatchReturnsFalseWhenNoCustomObjectFiltersPresent(): void
    {
        $filters = [
            $this->buildLeadFilter('email', '=', 'test@example.com'),
        ];

        $hasCustomFields = false;
        $result          = $this->matcher->match($filters, ['id' => 1, 'email' => 'test@example.com'], $hasCustomFields);

        $this->assertFalse($result);
        $this->assertFalse($hasCustomFields);
        $this->customObjectModel->expects($this->never())->method('fetchEntity');
    }

    public function testMatchReturnsFalseWhenCustomObjectFetchThrowsNotFoundException(): void
    {
        $this->customObjectModel->method('fetchEntity')
            ->willThrowException(new NotFoundException('Custom object not found'));

        $hasCustomFields = false;
        $result          = $this->matcher->match([$this->buildCmoFilter('cmo_1', '=', 'Acme')], ['id' => 42], $hasCustomFields);

        $this->assertFalse($result);
        $this->assertFalse($hasCustomFields);
    }

    public function testMatchReturnsFalseWhenCustomObjectFetchThrowsInvalidCustomObjectFormatListException(): void
    {
        $this->customObjectModel->method('fetchEntity')
            ->willThrowException(new InvalidCustomObjectFormatListException('bad format'));

        $hasCustomFields = false;
        $result          = $this->matcher->match([$this->buildCmoFilter('cmo_1', '=', 'Acme')], ['id' => 42], $hasCustomFields);

        $this->assertFalse($result);
        $this->assertFalse($hasCustomFields);
    }

    public function testMatchReturnsFalseWhenContactHasNoLinkedCustomItems(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(5));
        $this->customItemModel->method('getArrayTableData')->willReturn([]);

        $hasCustomFields = false;
        $result          = $this->matcher->match([$this->buildCmoFilter('cmo_5', '=', 'Acme')], ['id' => 42], $hasCustomFields);

        $this->assertFalse($result);
        // hasCustomFields is true because the CO filter was found and processed
        $this->assertTrue($hasCustomFields);
    }

    public function testMatchReturnsTrueForEmptyOperatorWhenContactHasNoLinkedItems(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));
        $this->customItemModel->method('getArrayTableData')->willReturn([]);

        $filter = array_merge($this->buildCmoFilter('cmo_1', 'empty', ''), ['type' => 'text']);
        $result = $this->matcher->match([$filter], ['id' => 42]);

        $this->assertTrue($result);
    }

    public function testMatchReturnsFalseForNotEmptyOperatorWhenContactHasNoLinkedItems(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));
        $this->customItemModel->method('getArrayTableData')->willReturn([]);

        $filter = array_merge($this->buildCmoFilter('cmo_1', '!empty', ''), ['type' => 'text']);
        $result = $this->matcher->match([$filter], ['id' => 42]);

        $this->assertFalse($result);
    }

    public function testMatchReturnsTrueWhenItemNameMatchesEqualFilter(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(3));
        $this->customItemModel->method('getArrayTableData')->willReturn([['name' => 'Acme Corp']]);

        $result = $this->matcher->match([$this->buildCmoFilter('cmo_3', '=', 'Acme Corp')], ['id' => 42]);

        $this->assertTrue($result);
    }

    public function testMatchReturnsFalseWhenItemNameDoesNotMatchEqualFilter(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(3));
        $this->customItemModel->method('getArrayTableData')->willReturn([['name' => 'Wrong Corp']]);

        $result = $this->matcher->match([$this->buildCmoFilter('cmo_3', '=', 'Acme Corp')], ['id' => 42]);

        $this->assertFalse($result);
    }

    public function testMatchReturnsTrueWhenAnyLinkedItemNameMatchesFilter(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(3));
        $this->customItemModel->method('getArrayTableData')->willReturn([
            ['name' => 'First Item'],
            ['name' => 'Acme Corp'],
            ['name' => 'Third Item'],
        ]);

        $result = $this->matcher->match([$this->buildCmoFilter('cmo_3', '=', 'Acme Corp')], ['id' => 42]);

        $this->assertTrue($result);
    }

    public function testMatchSetsHasCustomFieldsToTrueWhenCustomObjectFilterIsProcessed(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));
        $this->customItemModel->method('getArrayTableData')->willReturn([['name' => 'Any']]);

        $hasCustomFields = false;
        $this->matcher->match([$this->buildCmoFilter('cmo_1', '=', 'Any')], ['id' => 42], $hasCustomFields);

        $this->assertTrue($hasCustomFields);
    }

    public function testCustomItemsAreFetchedOncePerObjectAndLeadAcrossMultipleFilters(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));

        $this->customItemModel->expects($this->once())
            ->method('getArrayTableData')
            ->willReturn([['name' => 'Acme']]);

        $filters = [
            $this->buildCmoFilter('cmo_1', '=', 'Acme'),
            $this->buildCmoFilter('cmo_1', '!=', 'Other'),
        ];

        $this->matcher->match($filters, ['id' => 42]);
    }

    public function testMatchFetchesCompanyDataWhenFilterFieldStartsWithCompany(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));
        $this->customItemModel->method('getArrayTableData')->willReturn([['name' => 'Test']]);

        $this->companyRepository->expects($this->once())
            ->method('getCompaniesByLeadId')
            ->with('42')
            ->willReturn([]);

        $filters = [
            $this->buildCmoFilter('cmo_1', '=', 'Test'),
            $this->buildLeadFilter('companycountry', '=', 'US'),
        ];

        $this->matcher->match($filters, ['id' => 42]);
    }

    public function testMatchDoesNotFetchCompanyDataWhenLeadAlreadyHasCompanies(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));
        $this->customItemModel->method('getArrayTableData')->willReturn([['name' => 'Test']]);

        $this->companyRepository->expects($this->never())->method('getCompaniesByLeadId');

        $filters = [
            $this->buildCmoFilter('cmo_1', '=', 'Test'),
            $this->buildLeadFilter('companycountry', '=', 'US'),
        ];

        $this->matcher->match($filters, ['id' => 42, 'companies' => [['companycountry' => 'US']]]);
    }

    public function testMatchFetchesTagsWhenFilterTypeIsTags(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));
        $this->customItemModel->method('getArrayTableData')->willReturn([['name' => 'Test']]);

        $result       = $this->createMock(Result::class);
        $queryBuilder = $this->createMock(DbalQueryBuilder::class);
        $result->method('fetchFirstColumn')->willReturn(['1', '5']);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);

        $this->connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $filters = [
            $this->buildCmoFilter('cmo_1', '=', 'Test'),
            ['object' => 'lead', 'field' => 'tags', 'type' => 'tags', 'operator' => 'in', 'filter' => ['1'], 'glue' => 'and'],
        ];

        $this->matcher->match($filters, ['id' => 42]);
    }

    public function testMatchDoesNotFetchTagsWhenLeadAlreadyHasTags(): void
    {
        $this->customObjectModel->method('fetchEntity')->willReturn($this->buildCustomObject(1));
        $this->customItemModel->method('getArrayTableData')->willReturn([['name' => 'Test']]);

        $this->connection->expects($this->never())->method('createQueryBuilder');

        $filters = [
            $this->buildCmoFilter('cmo_1', '=', 'Test'),
            ['object' => 'lead', 'field' => 'tags', 'type' => 'tags', 'operator' => 'in', 'filter' => ['1'], 'glue' => 'and'],
        ];

        $this->matcher->match($filters, ['id' => 42, 'tags' => ['1', '5']]);
    }

    // Helpers

    private function buildCustomObject(int $id): MockObject
    {
        $customObject = $this->createMock(CustomObject::class);
        $customObject->method('getId')->willReturn($id);

        return $customObject;
    }

    /**
     * @return mixed[]
     */
    private function buildCmoFilter(string $field, string $operator, string $filterValue): array
    {
        return [
            'object'   => 'custom_object',
            'field'    => $field,
            'type'     => 'text',
            'operator' => $operator,
            'filter'   => $filterValue,
            'glue'     => 'and',
        ];
    }

    /**
     * @return mixed[]
     */
    private function buildLeadFilter(string $field, string $operator, string $filterValue): array
    {
        return [
            'object'   => 'lead',
            'field'    => $field,
            'type'     => 'text',
            'operator' => $operator,
            'filter'   => $filterValue,
            'glue'     => 'and',
        ];
    }
}
