<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\AbstractQuery;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;

class CustomFieldValueDateTimeFunctionalTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    private CustomObject $customObject;

    protected function setUp(): void
    {
        $this->configParams['default_timezone'] = 'America/New_York';

        parent::setUp();

        $this->customObject = $this->createCustomObjectWithAllFields(self::$container, 'Event');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function dataApiCreateCustomItemWithDatetime(): iterable
    {
        yield 'UTC date' => ['2027-06-07T12:00:00Z', '2027-06-07T12:00:00+00:00'];
        yield 'Date with offset' => ['2027-06-07T17:00:00+03:00', '2027-06-07T14:00:00+00:00'];
        yield 'Date without TZ' => ['2027-06-07T08:00:00', '2027-06-07T08:00:00+00:00'];
    }

    /**
     * @dataProvider dataApiCreateCustomItemWithDatetime
     */
    public function testApiCreateCustomItemWithDatetime(string $inputDate, string $expectedDate): void
    {
        $contact = [
            'email'         => 'tz-test@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $this->customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'TZ Test Item',
                                'attributes' => [
                                    'datetime-test-field' => $inputDate,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', '/api/contacts/new?includeCustomObjects=true', $contact);
        $this->assertResponseHasExpectedDate($expectedDate);
    }

    /**
     * @dataProvider dataApiCreateCustomItemWithDatetime
     */
    public function testApiUpdateCustomItemWithDatetime(string $inputDate, string $expectedDate): void
    {
        $contact = [
            'email'         => 'tz-test@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $this->customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'TZ Test Item',
                                'attributes' => [
                                    'datetime-test-field' => '2027-06-07T12:00:00Z',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', '/api/contacts/new?includeCustomObjects=true', $contact);
        $responseData = $this->getResponseDecoded();
        $contactId    = $responseData['contact']['id'];
        $customItemId = $this->fetchCustomItemId();

        $updateContact = [
            'email'         => 'tz-test@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $this->customObject->getId(),
                        'data' => [
                            [
                                'id'         => $customItemId,
                                'name'       => 'TZ Test Item Updated',
                                'attributes' => [
                                    'datetime-test-field' => $inputDate,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('PATCH', "/api/contacts/$contactId/edit?includeCustomObjects=true", $updateContact);
        $this->assertResponseHasExpectedDate($expectedDate);
    }

    public function testApiDatetimeDoesNotDriftOnReRead(): void
    {
        $contact = [
            'email'         => 'tz-test@api.test',
            'customObjects' => [
                'data' => [
                    [
                        'id'   => $this->customObject->getId(),
                        'data' => [
                            [
                                'name'       => 'TZ Drift Test',
                                'attributes' => [
                                    'datetime-test-field' => '2027-06-07T12:00:00-06:00',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', '/api/contacts/new?includeCustomObjects=true', $contact);
        $this->assertResponseIsSuccessful();
        $responseData = $this->getResponseDecoded();
        $contactId    = $responseData['contact']['id'];
        $firstRead    = $responseData['contact']['customObjects']['data'][0]['data'][0]['attributes']['datetime-test-field'];

        $this->client->request('GET', "/api/contacts/{$contactId}?includeCustomObjects=true");
        $this->assertResponseIsSuccessful();
        $responseData = $this->getResponseDecoded();
        $secondRead   = $responseData['contact']['customObjects']['data'][0]['data'][0]['attributes']['datetime-test-field'];

        $this->assertSame(
            $firstRead,
            $secondRead,
            'Datetime value must not drift between write and subsequent read.'
        );
    }

    public function testUiFormSubmitStoresDatetimeCorrectly(): void
    {
        $inputDate     = '2027-06-07 08:00';
        $datetimeField = $this->getDatetimeField($this->customObject);

        $crawler = $this->client->request('GET', sprintf('/s/custom/object/%d/item/new', $this->customObject->getId()));
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();

        $form['custom_item[name]']                                                    = 'UI TZ Test';
        $form['custom_item[custom_field_values]['.$datetimeField->getId().'][value]'] = $inputDate;

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $customItemId = $this->fetchCustomItemId();
        $storedValue  = $this->fetchDateTimeFieldValue($customItemId);
        $this->assertNotFalse($storedValue, 'Datetime value must be stored in the database');

        $storedDt = new DateTimeImmutable($storedValue, new DateTimeZone('UTC'));
        $inputDt  = new DateTimeImmutable($inputDate, new DateTimeZone('America/New_York'));

        $this->assertSame(
            $inputDt->getTimestamp(),
            $storedDt->getTimestamp(),
            sprintf(
                'UI input "%s" in America/New_York should be stored as UTC equivalent. Stored: %s.',
                $inputDate,
                $storedValue
            )
        );

        $this->em->clear();
        $crawler = $this->client->request('GET', sprintf('/s/custom/object/%d/item/edit/%d', $this->customObject->getId(), $customItemId));
        $this->assertResponseIsSuccessful();
        $uiDate = $crawler->filterXPath('//input[@name="custom_item[custom_field_values]['.$datetimeField->getId().'][value]"]')->attr('value');

        $this->assertSame(
            $inputDate,
            $uiDate,
            sprintf(
                'Input date "%s" in America/New_York should be presented as America/New_York in the UI. UI: %s.',
                $inputDate,
                $uiDate
            )
        );
    }

    private function assertStoredUtcValue(string $expectedUtcString, string $customItemId): void
    {
        $storedValue = $this->fetchDateTimeFieldValue($customItemId);

        $this->assertSame(
            (new DateTimeImmutable($expectedUtcString))->format('Y-m-d H:i:s'),
            $storedValue,
            sprintf('Database should store UTC value "%s" but found "%s"', $expectedUtcString, $storedValue)
        );
    }

    /**
     * @return false|mixed
     */
    private function fetchDateTimeFieldValue(string $customItemId)
    {
        return $this->connection->fetchOne(
            'SELECT value FROM '.MAUTIC_TABLE_PREFIX.'custom_field_value_datetime WHERE custom_item_id = :id',
            ['id' => $customItemId]
        );
    }

    private function getDatetimeField(CustomObject $customObject): CustomField
    {
        foreach ($customObject->getCustomFields() as $field) {
            if ('datetime' === $field->getType()) {
                return $field;
            }
        }

        $this->fail('Custom object must have a datetime field');
    }

    private function fetchCustomItemId(): string
    {
        return $this->em->createQueryBuilder()
            ->select('IDENTITY(v.customItem)')
            ->from(CustomFieldValueDateTime::class, 'v')
            ->getQuery()
            ->getSingleResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * @return mixed[]
     */
    private function getResponseDecoded(): array
    {
        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);

        return json_decode($content, true);
    }

    private function assertResponseHasExpectedDate(string $expectedDate): void
    {
        $this->assertResponseIsSuccessful();
        $responseData = $this->getResponseDecoded();

        $customItem = $responseData['contact']['customObjects']['data'][0]['data'][0];
        $this->assertSame($expectedDate, $customItem['attributes']['datetime-test-field']);
        $this->assertStoredUtcValue($expectedDate, (string) $customItem['id']);
    }
}
