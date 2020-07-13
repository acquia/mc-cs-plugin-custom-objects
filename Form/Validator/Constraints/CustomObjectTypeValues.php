<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CustomObjectTypeValues extends Constraint
{
    public $missingMasterObject = "Objects of type 'Relationship' must select a master object.";

    public function validatedBy()
    {
        return CustomObjectTypeValuesValidator::class;
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
