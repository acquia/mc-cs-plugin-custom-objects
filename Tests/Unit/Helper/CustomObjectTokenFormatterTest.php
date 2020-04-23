<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Helper;

use MauticPlugin\CustomObjectsBundle\Exception\InvalidCustomObjectFormatListException;
use MauticPlugin\CustomObjectsBundle\Helper\CustomObjectTokenFormatter as Formatter;
use \PHPUnit\Framework\TestCase;

class CustomObjectTokenFormatterTest extends TestCase
{
    private $manyValues = ['value1', 'value2', 'value3'];

    private $oneValue = ['oneValue'];

    private $noValues = [];

    public function testFormatFunction(): void
    {
        $this->expectException(InvalidCustomObjectFormatListException::class);
        Formatter::format($this->manyValues, 'A BAD FORMAT');

        // With no values
        $this->assertEquals('', Formatter::format($this->noValues, 'or-list'));

        // With one value
        $this->assertEquals('oneValue', Formatter::format($this->oneValue, 'and-list'));

        // With many values
        $expected = 'value1, value2 and value3';
        $this->assertEquals($expected, Formatter::format($this->manyValues, 'and-list'));
    }

    /**
     * Make sure the formatter has at least the default format and that it is valid
     */
    public function testDefaultFormat(): void
    {
        // Check the list of formats isn't empty
        $this->assertGreaterThan(0, count(Formatter::FORMATS));

        // Check that one of the formats is the default one
        $this->assertTrue(array_key_exists('default', Formatter::FORMATS));

        // the default format method should stay the same
        $this->assertEquals('formatDefault', Formatter::FORMATS[Formatter::DEFAULT_FORMAT]);

        // Check that the default format is valid
        $this->assertTrue(Formatter::isValidFormat('default'));

        // Actually check the output of the format

        // With no values
        $this->assertEquals('', Formatter::formatDefault($this->noValues));

        // With one value
        $this->assertEquals('oneValue', Formatter::formatDefault($this->oneValue));

        // With many values
        $expected = 'value1, value2, value3';
        $this->assertEquals($expected, Formatter::formatDefault($this->manyValues));
    }

    public function testFormatOrList(): void
    {
        // With no values
        $this->assertEquals('', Formatter::formatOrList($this->noValues));

        // With one value
        $this->assertEquals('oneValue', Formatter::formatOrList($this->oneValue));

        // With many values
        $expected = 'value1, value2 or value3';
        $this->assertEquals($expected, Formatter::formatOrList($this->manyValues));
    }

    public function testFormatAndList(): void
    {
        // With no values
        $this->assertEquals('', Formatter::formatAndList($this->noValues));

        // With one value
        $this->assertEquals('oneValue', Formatter::formatAndList($this->oneValue));

        // With many values
        $expected = 'value1, value2 and value3';
        $this->assertEquals($expected, Formatter::formatAndList($this->manyValues));
    }

    public function testFormatBulletList(): void
    {
        // With no values
        $this->assertEquals('', Formatter::formatBulletList($this->noValues));

        // With one value
        $expected = '<ul><li>oneValue</li></ul>';
        $this->assertEquals($expected, Formatter::formatBulletList($this->oneValue));

        // With many values
        $expected = '<ul><li>value1</li><li>value2</li><li>value3</li></ul>';
        $this->assertEquals($expected, Formatter::formatBulletList($this->manyValues));
    }

    public function testFormatOrderedList(): void
    {
        // With no values
        $this->assertEquals('', Formatter::formatOrderedList($this->noValues));

        // With one value
        $expected = '<ol><li>oneValue</li></ol>';
        $this->assertEquals($expected, Formatter::formatOrderedList($this->oneValue));

        // With many values
        $expected = '<ol><li>value1</li><li>value2</li><li>value3</li></ol>';
        $this->assertEquals($expected, Formatter::formatOrderedList($this->manyValues));
    }

    public function testFormatIsValidFunction(): void
    {
        // Make sure our invalid key actually doesn't exists
        $this->assertFalse(array_key_exists('AN INVALID FORMAT', Formatter::FORMATS));

        $this->assertFalse(Formatter::isValidFormat('AN INVALID FORMAT'));
    }
}
