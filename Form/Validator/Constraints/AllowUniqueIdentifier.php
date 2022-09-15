<?php

namespace MauticPlugin\CustomObjectsBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class AllowUniqueIdentifier extends Constraint
{
    public string $message = 'custom.field.allow.unique_identifier.invalid';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
