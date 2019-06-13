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

use MauticPlugin\CustomObjectsBundle\CustomFieldType\UrlType;
use Symfony\Component\Translation\TranslatorInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueText;
use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UrlTypeTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $customField;
    private $customItem;
    private $context;
    private $violation;

    /**
     * @var UrlType
     */
    private $fieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator  = $this->createMock(TranslatorInterface::class);
        $this->customField = $this->createMock(CustomField::class);
        $this->customItem  = $this->createMock(CustomItem::class);
        $this->context     = $this->createMock(ExecutionContextInterface::class);
        $this->violation   = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->fieldType   = new UrlType($this->translator);

        $this->context->method('buildViolation')->willReturn($this->violation);
    }

    public function testValidateValueWithBogusString(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, 'unicorn');

        $this->violation->expects($this->once())
            ->method('atPath')
            ->with('value')
            ->willReturnSelf();

        $this->violation->expects($this->once())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithEmptyString(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, '');

        $this->violation->expects($this->never())
            ->method('atPath');

        $this->violation->expects($this->never())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }

    public function testValidateValueWithValidUrl(): void
    {
        $valueEntity = new CustomFieldValueText($this->customField, $this->customItem, 'https://mautic.org');

        $this->violation->expects($this->never())
            ->method('atPath');

        $this->violation->expects($this->never())
            ->method('addViolation');

        $this->fieldType->validateValue($valueEntity, $this->context);
    }
}
