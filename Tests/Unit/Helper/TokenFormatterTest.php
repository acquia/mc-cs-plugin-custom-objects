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

    private $availableFormats = [
        TokenFormatter::DEFAULT_FORMAT      => 'formatDefault',
        TokenFormatter::OR_LIST_FORMAT      => 'formatOrList',
        TokenFormatter::AND_LIST_FORMAT     => 'formatAndList',
        TokenFormatter::BULLET_LIST_FORMAT  => 'formatBulletList',
        TokenFormatter::ORDERED_LIST_FORMAT => 'formatOrderedList',
    ];

    public function setUp(): void
    {
        $this->formatter = new TokenFormatter();
    }

    public function testFormatFail(): void
    {
        $this->assertSame(
            '',
            $this->formatter->format($this->noValues, 'A BAD FORMAT')
        );

        $this->expectException(InvalidCustomObjectFormatListException::class);
        $this->formatter->format($this->manyValues, 'A BAD FORMAT');
    }

    public function testFormatOneValue()
    {
        $this->assertEquals(
            'oneValue',
            $this->formatter->format($this->oneValue, TokenFormatter::AND_LIST_FORMAT)
        );
    }

    public function testFormatManyValues()
    {
        $this->assertEquals(
            'value1, value2 and value3',
            $this->formatter->format($this->manyValues, TokenFormatter::AND_LIST_FORMAT)
        );
    }

    public function testAvailableFormats()
    {
        $this->assertSame(
            $this->availableFormats,
            TokenFormatter::FORMATS
        );
    }

    public function testIsValidFormat()
    {
        foreach ($this->availableFormats as $format => $methodName) {
            $this->assertTrue($this->formatter->isValidFormat($format));
        }

        $this->assertFalse($this->formatter->isValidFormat('noFormat'));
    }

    public function testFormatDefault(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->format($this->noValues, TokenFormatter::DEFAULT_FORMAT));

        // With one value
        $this->assertEquals('oneValue', $this->formatter->format($this->oneValue, TokenFormatter::DEFAULT_FORMAT));

        // With many values
        $expected = 'value1, value2, value3';
        $this->assertEquals($expected, $this->formatter->format($this->manyValues, TokenFormatter::DEFAULT_FORMAT));
    }

    public function testFormatOrList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->format($this->noValues, TokenFormatter::OR_LIST_FORMAT));

        // With one value
        $this->assertEquals('oneValue', $this->formatter->format($this->oneValue, TokenFormatter::OR_LIST_FORMAT));

        // With many values
        $expected = 'value1, value2 or value3';
        $this->assertEquals($expected, $this->formatter->format($this->manyValues, TokenFormatter::OR_LIST_FORMAT));
    }

    public function testFormatAndList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->format($this->noValues, TokenFormatter::AND_LIST_FORMAT));

        // With one value
        $this->assertEquals('oneValue', $this->formatter->format($this->oneValue, TokenFormatter::AND_LIST_FORMAT));

        // With many values
        $expected = 'value1, value2 and value3';
        $this->assertEquals($expected, $this->formatter->format($this->manyValues, TokenFormatter::AND_LIST_FORMAT));
    }

    public function testFormatBulletList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->format($this->noValues, TokenFormatter::BULLET_LIST_FORMAT));

        // With one value
        $expected = '<ul><li>oneValue</li></ul>';
        $this->assertEquals($expected, $this->formatter->format($this->oneValue, TokenFormatter::BULLET_LIST_FORMAT));

        // With many values
        $expected = '<ul><li>value1</li><li>value2</li><li>value3</li></ul>';
        $this->assertEquals($expected, $this->formatter->format($this->manyValues, TokenFormatter::BULLET_LIST_FORMAT));
    }

    public function testFormatOrderedList(): void
    {
        // With no values
        $this->assertEquals('', $this->formatter->format($this->noValues, TokenFormatter::ORDERED_LIST_FORMAT));

        // With one value
        $expected = '<ol><li>oneValue</li></ol>';
        $this->assertEquals($expected, $this->formatter->format($this->oneValue, TokenFormatter::ORDERED_LIST_FORMAT));

        // With many values
        $expected = '<ol><li>value1</li><li>value2</li><li>value3</li></ol>';
        $this->assertEquals($expected, $this->formatter->format($this->manyValues, TokenFormatter::ORDERED_LIST_FORMAT));
    }
}
