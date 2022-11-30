<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookingClassValidator extends ConstraintValidator
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof BookingClass) {
            throw new UnexpectedTypeException($constraint, BookingClass::class);
        }

        if (null === $value->getStartDate() || null === $value->getEndDate()) {
            return;
        }

        if (!($value->getEndDate() > $value->getStartDate())) {
            $this->context->buildViolation($this->translator->trans('booking.end_date.posterior_to_start_date', [], 'validators'))
                ->atPath('endDate')
                ->addViolation();
        }
    }
}
