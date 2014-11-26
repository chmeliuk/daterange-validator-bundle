<?php

namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

abstract class AbstractDateRangeConstraint extends Constraint
{
    public $message = 'DateRanges with dates {{ intersects }} intersects';
    
    public $errorPath = array('startDate', 'endDate');
    
    public $startDateField = 'startDate';
    
    public $endDateField = 'endDate';
}
