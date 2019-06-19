<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\CustomFieldType;

use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\SelectType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SelectTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;

    /**
     * @var SelectType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->fieldType  = new SelectType($this->translator);
    }

    public function testGetSymfonyFormFieldType(): void
    {
        $this->assertSame(ChoiceType::class, $this->fieldType->getSymfonyFormFieldType());
    }

    public function testUseEmptyValue(): void
    {
        $this->assertTrue($this->fieldType->useEmptyValue());
    }

    public function testGetOperators(): void
    {
        $operators = $this->fieldType->getOperators();

        $this->assertCount(4, $operators);
        $this->assertArrayHasKey('=', $operators);
        $this->assertArrayHasKey('!=', $operators);
        $this->assertArrayHasKey('empty', $operators);
        $this->assertArrayHasKey('!empty', $operators);
        $this->assertArrayNotHasKey('in', $operators);
    }
}
