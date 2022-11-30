<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class BookingClass extends Constraint
{
    public function validatedBy()
    {
        return static::class.'Validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
