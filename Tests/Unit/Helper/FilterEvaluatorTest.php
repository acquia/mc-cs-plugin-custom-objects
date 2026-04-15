<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
use MauticPlugin\CustomObjectsBundle\Helper\FilterEvaluator;
use PHPUnit\Framework\TestCase;

class FilterEvaluatorTest extends TestCase
{
    private FilterEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new FilterEvaluator();
    }

    // -------------------------------------------------------------------------
    // Guard conditions
    // -------------------------------------------------------------------------

    public function testReturnsFalseWhenLeadHasNoId(): void
    {
        $filters = [$this->leadFilter('email', 'text', '=', 'test@example.com')];
        $this->assertFalse($this->evaluator->evaluate($filters, ['email' => 'test@example.com']));
    }

    public function testReturnsFalseWhenFiltersAreEmpty(): void
    {
        $this->assertFalse($this->evaluator->evaluate([], ['id' => 1]));
    }

    public function testSkipsFilterWhenFieldNotPresentInLead(): void
    {
        // Filter references a field that is not in $lead — should be skipped, result is false.
        $filters = [$this->leadFilter('missing_field', 'text', '=', 'value')];
        $this->assertFalse($this->evaluator->evaluate($filters, ['id' => 1]));
    }

    // -------------------------------------------------------------------------
    // AND / OR group logic
    // -------------------------------------------------------------------------

    public function testAndFiltersAllMustBeTrue(): void
    {
        $filters = [
            $this->leadFilter('first_name', 'text', '=', 'Alice'),
            $this->leadFilter('last_name', 'text', '=', 'Smith'),
        ];

        $this->assertTrue(
            $this->evaluator->evaluate($filters, ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Smith'])
        );

        $this->assertFalse(
            $this->evaluator->evaluate($filters, ['id' => 1, 'first_name' => 'Alice', 'last_name' => 'Jones'])
        );
    }

    public function testOrGroupPassesWhenFirstGroupFails(): void
    {
        $filters = [
            $this->leadFilter('email', 'text', '=', 'wrong@example.com'),
            array_merge($this->leadFilter('city', 'text', '=', 'Paris'), ['glue' => 'or']),
        ];

        $lead = ['id' => 1, 'email' => 'test@example.com', 'city' => 'Paris'];
        $this->assertTrue($this->evaluator->evaluate($filters, $lead));
    }

    public function testOrGroupPassesWhenFirstGroupPasses(): void
    {
        $filters = [
            $this->leadFilter('email', 'text', '=', 'test@example.com'),
            array_merge($this->leadFilter('city', 'text', '=', 'Wrong'), ['glue' => 'or']),
        ];

        $lead = ['id' => 1, 'email' => 'test@example.com', 'city' => 'Paris'];
        $this->assertTrue($this->evaluator->evaluate($filters, $lead));
    }

    public function testReturnsFalseWhenAllOrGroupsFail(): void
    {
        $filters = [
            $this->leadFilter('email', 'text', '=', 'wrong@example.com'),
            array_merge($this->leadFilter('city', 'text', '=', 'Wrong'), ['glue' => 'or']),
        ];

        $lead = ['id' => 1, 'email' => 'test@example.com', 'city' => 'Paris'];
        $this->assertFalse($this->evaluator->evaluate($filters, $lead));
    }

    // -------------------------------------------------------------------------
    // Custom object any-match semantics
    // -------------------------------------------------------------------------

    public function testCustomObjectAnyItemMatchWins(): void
    {
        $filters = [$this->cmoFilter('cmf_1', 'text', '=', 'premium')];
        $lead    = ['id' => 1, 'cmf_1' => ['basic', 'premium', 'trial']];

        $this->assertTrue($this->evaluator->evaluate($filters, $lead));
    }

    public function testCustomObjectReturnsFalseWhenNoItemMatches(): void
    {
        $filters = [$this->cmoFilter('cmf_1', 'text', '=', 'enterprise')];
        $lead    = ['id' => 1, 'cmf_1' => ['basic', 'premium']];

        $this->assertFalse($this->evaluator->evaluate($filters, $lead));
    }

    public function testCustomObjectEmptyOperatorMatchesWhenNoLinkedItems(): void
    {
        $filters = [$this->cmoFilter('cmf_1', 'text', 'empty', null)];
        $lead    = ['id' => 1, 'cmf_1' => []];

        $this->assertTrue($this->evaluator->evaluate($filters, $lead));
    }

    public function testCustomObjectNotEmptyOperatorFailsWhenNoLinkedItems(): void
    {
        $filters = [$this->cmoFilter('cmf_1', 'text', '!empty', null)];
        $lead    = ['id' => 1, 'cmf_1' => []];

        $this->assertFalse($this->evaluator->evaluate($filters, $lead));
    }

    public function testCustomObjectScalarValueIsWrappedInArray(): void
    {
        // cmf_ value stored as scalar rather than array — evaluator must normalise it.
        $filters = [$this->cmoFilter('cmf_1', 'text', '=', 'hello')];
        $lead    = ['id' => 1, 'cmf_1' => 'hello'];

        $this->assertTrue($this->evaluator->evaluate($filters, $lead));
    }

    // -------------------------------------------------------------------------
    // Operators — string/text
    // -------------------------------------------------------------------------

    public function testEqualOperator(): void
    {
        $this->assertOperator('text', '=', 'abc', 'abc', true);
        $this->assertOperator('text', '=', 'abc', 'xyz', false);
    }

    public function testNotEqualOperator(): void
    {
        $this->assertOperator('text', '!=', 'abc', 'xyz', true);
        $this->assertOperator('text', '!=', 'abc', 'abc', false);
    }

    public function testEmptyOperator(): void
    {
        $this->assertOperator('text', 'empty', '', null, true);
        $this->assertOperator('text', 'empty', 'value', null, false);
    }

    public function testNotEmptyOperator(): void
    {
        $this->assertOperator('text', '!empty', 'value', null, true);
        $this->assertOperator('text', '!empty', '', null, false);
    }

    public function testLikeOperatorWithPercentWildcard(): void
    {
        $this->assertOperator('text', 'like', 'abracadabra', 'abra%', true);
        $this->assertOperator('text', 'like', 'abracadabra', '%cadabra', true);
        $this->assertOperator('text', 'like', 'abracadabra', '%cada%', true);
        $this->assertOperator('text', 'like', 'abracadabra', 'unicorn%', false);
    }

    public function testNotLikeOperator(): void
    {
        $this->assertOperator('text', '!like', 'abracadabra', 'unicorn%', true);
        $this->assertOperator('text', '!like', 'abracadabra', 'abra%', false);
    }

    public function testStartsWithOperator(): void
    {
        $this->assertOperator('text', 'startsWith', 'abracadabra', 'abra', true);
        $this->assertOperator('text', 'startsWith', 'abracadabra', 'cadabra', false);
    }

    public function testEndsWithOperator(): void
    {
        $this->assertOperator('text', 'endsWith', 'abracadabra', 'cadabra', true);
        $this->assertOperator('text', 'endsWith', 'abracadabra', 'unicorn', false);
    }

    public function testContainsOperator(): void
    {
        $this->assertOperator('text', 'contains', 'abracadabra', 'cada', true);
        $this->assertOperator('text', 'contains', 'abracadabra', 'unicorn', false);
    }

    public function testRegexpOperator(): void
    {
        $this->assertOperator('text', 'regexp', 'abracadabra', 'abra.*cadabra', true);
        $this->assertOperator('text', 'regexp', 'abracadabra', '^unicorn', false);
        // Regexp is case-insensitive
        $this->assertOperator('text', 'regexp', 'HELLO', 'hello', true);
    }

    public function testNotRegexpOperator(): void
    {
        $this->assertOperator('text', '!regexp', 'abracadabra', '^unicorn', true);
        $this->assertOperator('text', '!regexp', 'abracadabra', 'abra.*cadabra', false);
    }

    public function testInOperator(): void
    {
        $this->assertOperator('text', 'in', 'b', ['a', 'b', 'c'], true);
        $this->assertOperator('text', 'in', 'z', ['a', 'b', 'c'], false);
    }

    public function testNotInOperator(): void
    {
        $this->assertOperator('text', '!in', 'z', ['a', 'b', 'c'], true);
        $this->assertOperator('text', '!in', 'b', ['a', 'b', 'c'], false);
    }

    public function testUnknownOperatorThrowsException(): void
    {
        $this->expectException(OperatorsNotFoundException::class);

        $filters = [$this->leadFilter('email', 'text', 'nonexistent_operator', 'value')];
        $this->evaluator->evaluate($filters, ['id' => 1, 'email' => 'test@example.com']);
    }

    // -------------------------------------------------------------------------
    // Operators — comparison (number type)
    // -------------------------------------------------------------------------

    public function testGtOperator(): void
    {
        $this->assertOperator('number', 'gt', 10, 5, true);
        $this->assertOperator('number', 'gt', 5, 10, false);
        $this->assertOperator('number', 'gt', 5, 5, false);
    }

    public function testGteOperator(): void
    {
        $this->assertOperator('number', 'gte', 10, 5, true);
        $this->assertOperator('number', 'gte', 5, 5, true);
        $this->assertOperator('number', 'gte', 4, 5, false);
    }

    public function testLtOperator(): void
    {
        $this->assertOperator('number', 'lt', 3, 5, true);
        $this->assertOperator('number', 'lt', 5, 5, false);
        $this->assertOperator('number', 'lt', 10, 5, false);
    }

    public function testLteOperator(): void
    {
        $this->assertOperator('number', 'lte', 3, 5, true);
        $this->assertOperator('number', 'lte', 5, 5, true);
        $this->assertOperator('number', 'lte', 10, 5, false);
    }

    // -------------------------------------------------------------------------
    // Type coercions
    // -------------------------------------------------------------------------

    public function testBooleanCoercionEqualTrueValues(): void
    {
        // String '1' and int 1 are both coerced to true before comparison.
        $this->assertOperator('boolean', '=', '1', 1, true);
        $this->assertOperator('boolean', '=', '0', 0, true);
        $this->assertOperator('boolean', '=', '1', 0, false);
    }

    public function testBooleanStrictNotEqualUsesIdentity(): void
    {
        // For boolean type != uses strict identity after coercion.
        $this->assertOperator('boolean', '!=', '1', 0, true);
        $this->assertOperator('boolean', '!=', '0', 0, false);
    }

    public function testMultiselectCoercionSplitsByPipe(): void
    {
        // Pipe-separated string is split into an array before in-check.
        $this->assertOperator('multiselect', 'in', 'a|b|c', ['b'], true);
        $this->assertOperator('multiselect', 'in', 'a|b|c', ['z'], false);
    }

    public function testSelectCoercionSplitsByPipe(): void
    {
        $this->assertOperator('select', 'in', 'a|b', ['a'], true);
    }

    public function testDatetimeCoercionAppendsMissingSeconds(): void
    {
        // Lead value has H:i:s, filter only H:i — evaluator appends :00 to filter.
        $this->assertOperator('datetime', '=', '2024-01-01 12:00:00', '2024-01-01 12:00', true);
    }

    public function testNumberCoercionConvertsToInt(): void
    {
        // String '42' in lead and int 42 as filter — both coerced to int.
        $this->assertOperator('number', '=', '42', 42, true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param mixed $leadValue
     * @param mixed $filterValue
     */
    private function assertOperator(string $type, string $operator, $leadValue, $filterValue, bool $expected): void
    {
        $filters = [$this->leadFilter('field', $type, $operator, $filterValue)];
        $result  = $this->evaluator->evaluate($filters, ['id' => 1, 'field' => $leadValue]);

        $this->assertSame(
            $expected,
            $result,
            "Operator '{$operator}' (type: {$type}): lead=".json_encode($leadValue).' filter='.json_encode($filterValue)
        );
    }

    /**
     * @param mixed $filterValue
     *
     * @return array<string, mixed>
     */
    private function leadFilter(string $field, string $type, string $operator, $filterValue): array
    {
        return [
            'glue'     => 'and',
            'field'    => $field,
            'object'   => 'lead',
            'type'     => $type,
            'filter'   => $filterValue,
            'operator' => $operator,
        ];
    }

    /**
     * @param mixed $filterValue
     *
     * @return array<string, mixed>
     */
    private function cmoFilter(string $field, string $type, string $operator, $filterValue): array
    {
        return [
            'glue'     => 'and',
            'field'    => $field,
            'object'   => 'custom_object',
            'type'     => $type,
            'filter'   => $filterValue,
            'operator' => $operator,
        ];
    }
}
