<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;
use MauticPlugin\CustomObjectsBundle\Helper\TokenFormatter;
use PHPUnit\Framework\TestCase;

class TokenFormatterTest extends TestCase
{
    /**
     * @var TokenFormatter
     */
    private $formatter;

    private $manyValues = ['value1', 'value2', 'value3'];

    private $oneValue = ['oneValue'];

    private $noValues = [];

    public function setUp(): void
    {
        $this->formatter = new TokenFormatter();
    }

    public function testFormatFunction(): void
    {
        $this->expectException(InvalidCustomObjectFormatListException::class);
        $this->formatter->format($this->manyValues, 'A BAD FORMAT');

        // With no values
        $this->assertEquals('', $this->formatter->format($this->noValues, 'or-list'));

        // With one value
        $this->assertEquals('oneValue', $this->formatter->format($this->oneValue, 'and-list'));

        // With many values
        $expected = 'value1, value2 and value3';
        $this->assertEquals($expected, $this->formatter->format($this->manyValues, 'and-list'));
    }

    /**
     * Make sure the formatter has at least the default format and that it is valid
     */
    public function testDefaultFormat(): void
    {
        // Check the list of formats isn't empty
        $this->assertGreaterThan(0, count(TokenFormatter::FORMATS));

        // Check that one of the formats is the default one
        $this->assertTrue(array_key_exists('default', TokenFormatter::FORMATS));

        // the default format method should stay the same
        $this->assertEquals('formatDefault', TokenFormatter::FORMATS[TokenFormatter::DEFAULT_FORMAT]);

        // Check that the default format is valid
        $this->assertTrue($this->formatter->isValidFormat('default'));

        // Actually check the output of the format

        // With no values
        $this->assertEquals('', $this->formatter->formatDefault($this->noValues));

        // With one value
        $this->assertEquals('oneValue', $this->formatter->formatDefault($this->oneValue));

        // With many values
        $expected = 'value1, value2, value3';
        $this->assertEquals($expected, $this->formatter->formatDefault($this->manyValues));
    }

    public function testFormatOrList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->formatOrList($this->noValues));

        // With one value
        $this->assertEquals('oneValue', $this->formatter->formatOrList($this->oneValue));

        // With many values
        $expected = 'value1, value2 or value3';
        $this->assertEquals($expected, $this->formatter->formatOrList($this->manyValues));
    }

    public function testFormatAndList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->formatAndList($this->noValues));

        // With one value
        $this->assertEquals('oneValue', $this->formatter->formatAndList($this->oneValue));

        // With many values
        $expected = 'value1, value2 and value3';
        $this->assertEquals($expected, $this->formatter->formatAndList($this->manyValues));
    }

    public function testFormatBulletList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->formatBulletList($this->noValues));

        // With one value
        $expected = '<ul><li>oneValue</li></ul>';
        $this->assertEquals($expected, $this->formatter->formatBulletList($this->oneValue));

        // With many values
        $expected = '<ul><li>value1</li><li>value2</li><li>value3</li></ul>';
        $this->assertEquals($expected, $this->formatter->formatBulletList($this->manyValues));
    }

    public function testFormatOrderedList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->formatOrderedList($this->noValues));

        // With one value
        $expected = '<ol><li>oneValue</li></ol>';
        $this->assertEquals($expected, $this->formatter->formatOrderedList($this->oneValue));

        // With many values
        $expected = '<ol><li>value1</li><li>value2</li><li>value3</li></ol>';
        $this->assertEquals($expected, $this->formatter->formatOrderedList($this->manyValues));
    }

    public function testFormatIsValidFunction(): void
    {
        // Make sure our invalid key actually doesn't exists
        $this->assertFalse(array_key_exists('AN INVALID FORMAT', TokenFormatter::FORMATS));

        $this->assertFalse($this->formatter->isValidFormat('AN INVALID FORMAT'));
    }

    // @todo test InvalidCustomObjectFormatListException
}
