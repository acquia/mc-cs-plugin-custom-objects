<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\EventListener\DynamicContentSubscriber;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;

#[\AllowDynamicProperties]
class DynamicContentSubscriberTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    private CustomItemModel $customItemModel;
    private CustomFieldValueModel $customFieldValueModel;
    private DynamicContentSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel       = self::$container->get('mautic.custom.model.item');
        $this->customFieldValueModel = self::$container->get('mautic.custom.model.field.value');
        $this->subscriber            = self::$container->get(DynamicContentSubscriber::class);
    }

    public function testVariousOperators(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('operators@example.com');
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Test Item');
        $this->customFieldValueModel->createValuesForItem($customItem);

        $textValue        = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $urlValue         = $customItem->findCustomFieldValueForFieldAlias('url-test-field');
        $dateValue        = $customItem->findCustomFieldValueForFieldAlias('date-test-field');
        $datetimeValue    = $customItem->findCustomFieldValueForFieldAlias('datetime-test-field');
        $multiselectValue = $customItem->findCustomFieldValueForFieldAlias('multiselect-test-field');

        $textValue->setValue('abracadabra');
        $dateValue->setValue('2019-07-17');
        $datetimeValue->setValue('2019-07-17 13:00:00');
        $multiselectValue->setValue(['option_b']);
        // urlValue left empty intentionally

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $textFieldId        = $textValue->getCustomField()->getId();
        $urlFieldId         = $urlValue->getCustomField()->getId();
        $dateFieldId        = $dateValue->getCustomField()->getId();
        $datetimeFieldId    = $datetimeValue->getCustomField()->getId();
        $multiselectFieldId = $multiselectValue->getCustomField()->getId();

        // = match
        $this->assertMatched($contact, $textFieldId, 'text', '=', 'abracadabra');

        // = no match
        $this->assertNotMatched($contact, $textFieldId, 'text', '=', 'unicorn');

        // != match
        $this->assertMatched($contact, $textFieldId, 'text', '!=', 'unicorn');

        // != no match
        $this->assertNotMatched($contact, $textFieldId, 'text', '!=', 'abracadabra');

        // empty — url field has no value
        $this->assertMatched($contact, $urlFieldId, 'text', 'empty', null);

        // !empty — text field has a value
        $this->assertMatched($contact, $textFieldId, 'text', '!empty', null);

        // !empty — url field is empty, so !empty should not match
        $this->assertNotMatched($contact, $urlFieldId, 'text', '!empty', null);

        // startsWith match
        $this->assertMatched($contact, $textFieldId, 'text', 'startsWith', 'abra');

        // startsWith no match
        $this->assertNotMatched($contact, $textFieldId, 'text', 'startsWith', 'unicorn');

        // endsWith match
        $this->assertMatched($contact, $textFieldId, 'text', 'endsWith', 'cadabra');

        // endsWith no match
        $this->assertNotMatched($contact, $textFieldId, 'text', 'endsWith', 'unicorn');

        // contains match
        $this->assertMatched($contact, $textFieldId, 'text', 'contains', 'cada');

        // contains no match
        $this->assertNotMatched($contact, $textFieldId, 'text', 'contains', 'unicorn');

        // like with % wildcard match
        $this->assertMatched($contact, $textFieldId, 'text', 'like', 'abra%');

        // like no match
        $this->assertNotMatched($contact, $textFieldId, 'text', 'like', 'unicorn%');

        // !like no match (value matches pattern, so !like is false)
        $this->assertNotMatched($contact, $textFieldId, 'text', '!like', 'abra%');

        // !like match (value does not match pattern)
        $this->assertMatched($contact, $textFieldId, 'text', '!like', 'unicorn%');

        // in (multiselect) match
        $this->assertMatched($contact, $multiselectFieldId, 'multiselect', 'in', ['option_b']);

        // in (multiselect) no match
        $this->assertNotMatched($contact, $multiselectFieldId, 'multiselect', 'in', ['option_a']);

        // !in (multiselect) match
        $this->assertMatched($contact, $multiselectFieldId, 'multiselect', '!in', ['option_a']);

        // !in (multiselect) no match (value is in list)
        $this->assertNotMatched($contact, $multiselectFieldId, 'multiselect', '!in', ['option_b']);

        // date lt match
        $this->assertMatched($contact, $dateFieldId, 'date', 'lt', '2019-08-05');

        // date lt no match
        $this->assertNotMatched($contact, $dateFieldId, 'date', 'lt', '2019-06-05');

        // date gt match
        $this->assertMatched($contact, $dateFieldId, 'date', 'gt', '2019-06-05');

        // date gt no match
        $this->assertNotMatched($contact, $dateFieldId, 'date', 'gt', '2019-08-05');

        // datetime gt match
        $this->assertMatched($contact, $datetimeFieldId, 'datetime', 'gt', '2019-07-16 13:00:00');

        // datetime gt no match
        $this->assertNotMatched($contact, $datetimeFieldId, 'datetime', 'gt', '2019-07-18 13:00:00');
    }

    public function testAndFiltersAllMustMatch(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('and-match@example.com');
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Test Item');
        $this->customFieldValueModel->createValuesForItem($customItem);

        $textValue = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $urlValue  = $customItem->findCustomFieldValueForFieldAlias('url-test-field');
        $textValue->setValue('abracadabra');
        $urlValue->setValue('https://example.com');

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$textValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'abracadabra',
                'display'  => null,
                'operator' => '=',
            ],
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$urlValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',
            ],
        ];

        $event = new ContactFiltersEvaluateEvent($filters, $contact);
        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertTrue($event->isMatched(), 'Both AND conditions are true, should match');
    }

    public function testAndFiltersOneFailsNoMatch(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('and-no-match@example.com');
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Test Item');
        $this->customFieldValueModel->createValuesForItem($customItem);

        $textValue = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $urlValue  = $customItem->findCustomFieldValueForFieldAlias('url-test-field');
        $textValue->setValue('abracadabra');
        // urlValue left empty intentionally

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$textValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'abracadabra',
                'display'  => null,
                'operator' => '=',
            ],
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$urlValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',  // url is empty, so this fails
            ],
        ];

        $event = new ContactFiltersEvaluateEvent($filters, $contact);
        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertFalse($event->isMatched(), 'One AND condition fails, should not match');
    }

    public function testOrFiltersSecondGroupMatches(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('or-match@example.com');
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Test Item');
        $this->customFieldValueModel->createValuesForItem($customItem);

        $textValue = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $urlValue  = $customItem->findCustomFieldValueForFieldAlias('url-test-field');
        $textValue->setValue('abracadabra');
        $urlValue->setValue('https://example.com');

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$textValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'unicorn',       // first group fails
                'display'  => null,
                'operator' => '=',
            ],
            [
                'glue'     => 'or',            // starts a new group
                'field'    => 'cmf_'.$urlValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',        // second group passes
            ],
        ];

        $event = new ContactFiltersEvaluateEvent($filters, $contact);
        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertTrue($event->isMatched(), 'Second OR group passes, overall result should match');
    }

    public function testMultipleLinkedItemsAnyMatchWins(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('multi-item@example.com');

        $itemA = new CustomItem($customObject);
        $itemA->setName('Item A');
        $this->customFieldValueModel->createValuesForItem($itemA);
        $itemA->findCustomFieldValueForFieldAlias('text-test-field')->setValue('basic');
        $itemA = $this->customItemModel->save($itemA);
        $this->customItemModel->linkEntity($itemA, 'contact', (int) $contact->getId());

        $itemB = new CustomItem($customObject);
        $itemB->setName('Item B');
        $this->customFieldValueModel->createValuesForItem($itemB);
        $textValueB = $itemB->findCustomFieldValueForFieldAlias('text-test-field');
        $textValueB->setValue('premium');
        $itemB = $this->customItemModel->save($itemB);
        $this->customItemModel->linkEntity($itemB, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$textValueB->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'premium',
                'display'  => null,
                'operator' => '=',
            ],
        ];

        $event = new ContactFiltersEvaluateEvent($filters, $contact);
        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertTrue($event->isMatched(), 'When any linked item matches, overall result should match');
    }

    public function testEmptyOperatorMatchesContactWithNoLinkedItems(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('no-items@example.com');
        // No custom item is created or linked to this contact

        // To make hasCustomObjectFilters() pass (so the subscriber takes ownership),
        // we still need a filter with a valid custom field ID. Create a throwaway item
        // just to get a field ID, but don't link it to this contact.
        $tempItem = new CustomItem($customObject);
        $tempItem->setName('Temp');
        $this->customFieldValueModel->createValuesForItem($tempItem);
        $textValue = $tempItem->findCustomFieldValueForFieldAlias('text-test-field');
        $this->customItemModel->save($tempItem);
        // Not linked to $contact

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$textValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => null,
                'display'  => null,
                'operator' => 'empty',
            ],
        ];

        $event = new ContactFiltersEvaluateEvent($filters, $contact);
        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertTrue($event->isMatched(), 'Contact with no linked items should match the "empty" operator');
    }

    public function testAlreadyEvaluatedEventIsSkipped(): void
    {
        // No DB setup needed — the subscriber bails out before touching the DB.
        $contact = new Lead();
        $event   = new ContactFiltersEvaluateEvent([], $contact);
        $event->setIsEvaluated(true);  // already handled by another listener
        $event->setIsMatched(false);   // prior result was false

        $this->subscriber->evaluateFilters($event);

        $this->assertFalse($event->isMatched(), 'Subscriber must not overwrite a result already set by another listener');
    }

    public function testRegexpOperator(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('regexp@example.com');
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Test Item');
        $this->customFieldValueModel->createValuesForItem($customItem);

        $textValue = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $textValue->setValue('abracadabra');
        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $fieldId = $textValue->getCustomField()->getId();

        // regexp match
        $this->assertMatched($contact, $fieldId, 'text', 'regexp', 'abra.*cadabra');

        // regexp no match
        $this->assertNotMatched($contact, $fieldId, 'text', 'regexp', '^unicorn');

        // !regexp match (value does not match pattern)
        $this->assertMatched($contact, $fieldId, 'text', '!regexp', '^unicorn');

        // !regexp no match (value matches pattern)
        $this->assertNotMatched($contact, $fieldId, 'text', '!regexp', 'abra.*cadabra');
    }

    public function testNumberFieldComparisons(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('number@example.com');
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Test Item');
        $this->customFieldValueModel->createValuesForItem($customItem);

        $intValue = $customItem->findCustomFieldValueForFieldAlias('int-test-field');
        $intValue->setValue(42);
        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $fieldId = $intValue->getCustomField()->getId();

        // = match / no match
        $this->assertMatched($contact, $fieldId, 'number', '=', 42);
        $this->assertNotMatched($contact, $fieldId, 'number', '=', 99);

        // gt / gte
        $this->assertMatched($contact, $fieldId, 'number', 'gt', 10);
        $this->assertNotMatched($contact, $fieldId, 'number', 'gt', 42);
        $this->assertMatched($contact, $fieldId, 'number', 'gte', 42);
        $this->assertNotMatched($contact, $fieldId, 'number', 'gte', 43);

        // lt / lte
        $this->assertMatched($contact, $fieldId, 'number', 'lt', 99);
        $this->assertNotMatched($contact, $fieldId, 'number', 'lt', 42);
        $this->assertMatched($contact, $fieldId, 'number', 'lte', 42);
        $this->assertNotMatched($contact, $fieldId, 'number', 'lte', 41);
    }

    public function testOrFiltersFirstGroupPassesSecondFails(): void
    {
        $customObject = $this->createCustomObjectWithAllFields(self::$container, 'Product');
        $contact      = $this->createContact('or-first-passes@example.com');
        $customItem   = new CustomItem($customObject);
        $customItem->setName('Test Item');
        $this->customFieldValueModel->createValuesForItem($customItem);

        $textValue = $customItem->findCustomFieldValueForFieldAlias('text-test-field');
        $urlValue  = $customItem->findCustomFieldValueForFieldAlias('url-test-field');
        $textValue->setValue('abracadabra');
        // urlValue left empty

        $customItem = $this->customItemModel->save($customItem);
        $this->customItemModel->linkEntity($customItem, 'contact', (int) $contact->getId());

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$textValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => 'abracadabra',
                'display'  => null,
                'operator' => '=',           // first group passes
            ],
            [
                'glue'     => 'or',           // starts a second group
                'field'    => 'cmf_'.$urlValue->getCustomField()->getId(),
                'object'   => 'custom_object',
                'type'     => 'text',
                'filter'   => null,
                'display'  => null,
                'operator' => '!empty',       // second group fails (url is empty)
            ],
        ];

        $event = new ContactFiltersEvaluateEvent($filters, $contact);
        $this->subscriber->evaluateFilters($event);

        $this->assertTrue($event->isEvaluated());
        $this->assertTrue($event->isMatched(), 'First OR group passes — overall result must be true even though second group fails');
    }

    public function testEvaluateFiltersSkipsEventWithNoCustomObjectFilters(): void
    {
        $contact = $this->createContact('test-skip@example.com');

        $filters = [
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'test-skip@example.com',
                'display'  => null,
                'operator' => '=',
            ],
        ];

        $event = new ContactFiltersEvaluateEvent($filters, $contact);
        $this->subscriber->evaluateFilters($event);

        $this->assertFalse($event->isEvaluated(), 'Subscriber should not take ownership when no custom object filters are present');
    }

    /**
     * @param mixed $filterValue
     */
    private function assertMatched(Lead $contact, int $fieldId, string $type, string $operator, $filterValue): void
    {
        $event = new ContactFiltersEvaluateEvent(
            $this->buildFilter($fieldId, $type, $operator, $filterValue),
            $contact
        );
        $this->subscriber->evaluateFilters($event);

        $this->assertTrue(
            $event->isMatched(),
            "Expected match for operator '{$operator}' with filter value '".json_encode($filterValue)."'"
        );
    }

    /**
     * @param mixed $filterValue
     */
    private function assertNotMatched(Lead $contact, int $fieldId, string $type, string $operator, $filterValue): void
    {
        $event = new ContactFiltersEvaluateEvent(
            $this->buildFilter($fieldId, $type, $operator, $filterValue),
            $contact
        );
        $this->subscriber->evaluateFilters($event);

        $this->assertFalse(
            $event->isMatched(),
            "Expected no match for operator '{$operator}' with filter value '".json_encode($filterValue)."'"
        );
    }

    /**
     * @param mixed $filterValue
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildFilter(int $fieldId, string $type, string $operator, $filterValue): array
    {
        return [
            [
                'glue'     => 'and',
                'field'    => 'cmf_'.$fieldId,
                'object'   => 'custom_object',
                'type'     => $type,
                'filter'   => $filterValue,
                'display'  => null,
                'operator' => $operator,
            ],
        ];
    }

    private function createContact(string $email): Lead
    {
        /** @var LeadModel $contactModel */
        $contactModel = self::$container->get('mautic.lead.model.lead');
        $contact      = new Lead();
        $contact->setEmail($email);
        $contactModel->saveEntity($contact);

        return $contact;
    }
}
