<?php
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
        $this->customObject->expects($this->never())
            ->method('getRelationship');

        $this->validator->validate($this->customObject, $this->constraint);
    }

    public function testValidateIgnoreRelationshipAndMasterObject()
    {
        $this->customObject->expects($this->once())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_RELATIONSHIP);
        $this->customObject->expects($this->once())
            ->method('getRelationship')
            ->willReturn(1);
        $this->customObject->expects($this->once())
            ->method('getMasterObject')
            ->willReturn(new CustomObject());

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($this->customObject, $this->constraint);
    }

    public function testValidateNoRelationship()
    {
        $this->customObject->expects($this->once())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_RELATIONSHIP);
        $this->customObject->expects($this->once())
            ->method('getRelationship')
            ->willReturn(null);
        $this->customObject->expects($this->once())
            ->method('getMasterObject')
            ->willReturn(new CustomObject());

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('relationship')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->missingRelationshipTypeMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($this->customObject, $this->constraint);
    }

    public function testValidateNoCustomObject()
    {
        $this->customObject->expects($this->once())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_RELATIONSHIP);
        $this->customObject->expects($this->once())
            ->method('getRelationship')
            ->willReturn(1);
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

    public function testValidateMissingBoth()
    {
        $this->customObject->expects($this->once())
            ->method('getType')
            ->willReturn(CustomObject::TYPE_RELATIONSHIP);
        $this->customObject->expects($this->once())
            ->method('getRelationship')
            ->willReturn(null);
        $this->customObject->expects($this->once())
            ->method('getMasterObject')
            ->willReturn(null);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder
            ->method('atPath')
            ->withConsecutive(['relationship'], ['masterObject'])
            ->willReturnOnConsecutiveCalls($violationBuilder, $violationBuilder);
        $violationBuilder->expects(new InvokedCountMatcher(2))
            ->method('addViolation');

        $this->context->expects($this->at(0))
            ->method('buildViolation')
            ->with($this->constraint->missingRelationshipTypeMessage)
            ->willReturn($violationBuilder);
        $this->context->expects($this->at(1))
            ->method('buildViolation')
            ->with($this->constraint->missingMasterObject)
            ->willReturn($violationBuilder);

        $this->validator->validate($this->customObject, $this->constraint);
    }
}
