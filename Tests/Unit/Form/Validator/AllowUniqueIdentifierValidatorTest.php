<?php

namespace MauticPlugin\CustomObjectsBundle\Tests\Unit\Form\Validator;

use MauticPlugin\CustomObjectsBundle\Entity\CustomField;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints\AllowUniqueIdentifierValidator;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Repository\CustomItemRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AllowUniqueIdentifierValidatorTest extends TestCase
{
    /**
     * @var CustomField|MockObject
     */
    private $customField;

    /**
     * @var MockObject|Constraint
     */
    private $constraint;

    /**
     * @var MockObject|ExecutionContextInterface
     */
    private $context;

    private AllowUniqueIdentifierValidator $validator;

    /**
     * @var CustomItemModel|MockObject
     */
    private $customItemModel;

    protected function setUp(): void
    {
        $this->customField = $this->createMock(CustomField::class);
        $this->constraint  = $this->createMock(Constraint::class);
        $this->context     = $this->createMock(ExecutionContextInterface::class);

        $this->customItemModel  = $this->createMock(CustomItemModel::class);
        $this->validator        = new AllowUniqueIdentifierValidator($this->customItemModel);
        $this->validator->initialize($this->context);

        parent::setUp();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function testInValidCustomFieldObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(null, $this->constraint);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function testAllowIsUniqueIdentifierInvalid(): void
    {
        $this->customField->expects($this->once())
            ->method('isNew')
            ->willReturn(false);

        $this->customField->expects($this->once())
            ->method('wasChangeIsUniqueIdentifier')
            ->willReturn(true);

        $customObject = $this->createMock(CustomObject::class);
        $customObject->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->customField->expects($this->once())
            ->method('getCustomObject')
            ->willReturn($customObject);

        $customItemRepositoryMock = $this->createMock(CustomItemRepository::class);
        $customItemRepositoryMock->expects($this->once())
            ->method('getItemCount')
            ->willReturn(2);

        $this->customItemModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($customItemRepositoryMock);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('isUniqueIdentifier')
            ->willReturn($violationBuilder);

        $violationBuilder->expects($this->once())
            ->method('addViolation');

        /** @phpstan-ignore-next-line */
        $message = $this->constraint->message;
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message)
            ->willReturn($violationBuilder);

        $this->validator->validate($this->customField, $this->constraint);
    }
}
