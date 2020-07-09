<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Validator;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints\CustomObjectTypeValuesValidator;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class CustomObjectTypeValuesValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomObject|MockObject
     */
    private $customObject;

    /**
     * @var MockObject|Constraint
     */
    private $constraint;

    /**
     * @var MockObject|ExecutionContextInterface
     */
    private $context;

    /**
     * @var CustomObjectTypeValuesValidator
     */
    private $validator;

    public function setUp()
    {
        $this->customObject = $this->createMock(CustomObject::class);
        $this->constraint = $this->createMock(Constraint::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new CustomObjectTypeValuesValidator();
        $this->validator->initialize($this->context);

        parent::setUp();
    }

    public function testValidateIgnoreCompletely()
    {
        $this->customObject->expects($this->once())
            ->method('getType')
            ->willReturn(999999);

        $this->validator->validate($this->customObject, $this->constraint);
    }

    public function testValidateIgnoreMasterObject()
    {
        $this->customObject->expects($this->once())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_RELATIONSHIP);
        $this->customObject->expects($this->once())
            ->method('getMasterObject')
            ->willReturn(new CustomObject());

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($this->customObject, $this->constraint);
    }

    public function testValidateNoCustomObject()
    {
        $this->customObject->expects($this->once())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_RELATIONSHIP);

        $this->customObject->expects($this->once())
            ->method('getMasterObject')
            ->willReturn(null);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('masterObject')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->missingMasterObject)
            ->willReturn($violationBuilder);

        $this->validator->validate($this->customObject, $this->constraint);
    }
}
