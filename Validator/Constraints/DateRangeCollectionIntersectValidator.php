<?php

namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DateRangeCollectionIntersectValidator extends AbstractDateRangeIntersectValidator
{
    public function validate($ranges, Constraint $constraint) 
    {
        // Check Constraint`s Type
        if (!$constraint instanceof DateRangeCollectionIntersect) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\DateRangeCollectionIntersect');
        }

        $invalidRanges = $this->validateRanges($ranges, $constraint);
        
        $this
            ->buildViolation(sprintf($constraint->message))
            ->setParameter('{{ intersects }}', implode(', ', $invalidRanges))
            ->addViolation()
        ;
    }
}
