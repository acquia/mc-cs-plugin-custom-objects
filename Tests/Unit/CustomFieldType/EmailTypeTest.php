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
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\CustomFieldType\EmailType;

class EmailTypeTest extends \PHPUnit\Framework\TestCase
{
    private $translator;
    private $customField;

    /**
     * @var EmailType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->fieldType   = new EmailType($this->translator);
    }

    public function testValidateValueWithBogusString(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('custom.field.email.invalid', ['%value%' => 'unicorn'], 'validators')
            ->willReturn('Translated message');

        $this->expectException(\UnexpectedValueException::class);
        $this->fieldType->validateValue($this->customField, 'unicorn');
    }

    public function testValidateValueWithEmptyString(): void
    {
        $this->fieldType->validateValue($this->customField, '');
        // No exception means it passes
        $this->addToAssertionCount(1);
    }

    public function testValidateValueWithValidEmailAddress(): void
    {
        $this->fieldType->validateValue($this->customField, 'hello@mautic.org');
        // No exception means it passes
        $this->addToAssertionCount(1);
    }
}
