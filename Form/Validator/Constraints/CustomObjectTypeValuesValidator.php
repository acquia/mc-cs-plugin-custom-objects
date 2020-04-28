<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints;

use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CustomObjectTypeValuesValidator extends ConstraintValidator
{
    /**
     * @param CustomObject      $customObject
     * @param CustomObjectTypeValues $constraint
     */
    public function validate($customObject, Constraint $constraint)
    {
        if (CustomObject::TYPE_RELATIONSHIP === $customObject->getType()) {
            if (null === $customObject->getRelationship()) {
                $this->context->buildViolation($constraint->missingRelationshipTypeMessage)
                    ->atPath('relationship')
                    ->addViolation();
            }

            if (null === $customObject->getMasterObject()) {
                $this->context->buildViolation($constraint->missingMasterObject)
                    ->atPath('masterObject')
                    ->addViolation();
            }
        }
    }
}
