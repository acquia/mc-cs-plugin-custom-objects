<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Entity;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueDateTime;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;

class CustomFieldValueDateTimeTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters(): void
    {
        $value       = new \DateTimeImmutable('2019-06-25 12:43:33');
        $customField = new CustomField();
        $customItem  = new CustomItem(new CustomObject());
        $optionValue = new CustomFieldValueDateTime($customField, $customItem, $value);

        $this->assertSame($customField, $optionValue->getCustomField());
        $this->assertSame($customItem, $optionValue->getCustomItem());
        $this->assertSame($value, $optionValue->getValue());

        $optionValue->setValue(null);

        $this->assertNull($optionValue->getValue());

        $optionValue->setValue('');

        $this->assertNull($optionValue->getValue());

        $optionValue->setValue($value);

        $this->assertEquals($value, $optionValue->getValue());

        $optionValue->setValue('2019-06-23 12:43:33');

        $this->assertSame('2019-06-23T12:43:33+00:00', $optionValue->getValue()->format(DATE_ATOM));
    }

    /**
     * @return iterable<string, array{string, mixed, string}>
     */
    public function provideSetValueConvertsTimezoneToLocal(): iterable
    {
        yield 'UTC string to New York' => [
            'America/New_York',
            '2027-06-07T12:00:00Z',
            '2027-06-07T08:00:00-04:00',
        ];

        yield 'UTC DateTimeImmutable to New York' => [
            'America/New_York',
            new \DateTimeImmutable('2027-06-07T12:00:00Z'),
            '2027-06-07T08:00:00-04:00',
        ];

        yield 'UTC DateTime to New York' => [
            'America/New_York',
            new \DateTime('2027-06-07T12:00:00Z'),
            '2027-06-07T08:00:00-04:00',
        ];

        yield 'UTC string to Asia/Kolkata' => [
            'Asia/Kolkata',
            '2027-06-07T12:00:00Z',
            '2027-06-07T17:30:00+05:30',
        ];

        yield 'positive offset string to New York' => [
            'America/New_York',
            '2027-06-07T15:00:00+03:00',
            '2027-06-07T08:00:00-04:00',
        ];

        yield 'no timezone string converts UTC to local' => [
            'America/New_York',
            '2027-06-07T12:00:00',
            '2027-06-07T08:00:00-04:00',
        ];
    }

    /**
     * @dataProvider provideSetValueConvertsTimezoneToLocal
     *
     * @param mixed $value
     */
    public function testSetValueConvertsTimezoneToLocal(string $timezone, $value, string $expectedAtom): void
    {
        $originalTz = date_default_timezone_get();
        date_default_timezone_set($timezone);

        try {
            $fieldValue  = new CustomFieldValueDateTime(new CustomField(), new CustomItem(new CustomObject()));
            $fieldValue->setValue($value);

            $result = $fieldValue->getValue();
            $this->assertSame($timezone, $result->getTimezone()->getName());
            $this->assertSame($expectedAtom, $result->format(DATE_ATOM));
        } finally {
            date_default_timezone_set($originalTz);
        }
    }
}
