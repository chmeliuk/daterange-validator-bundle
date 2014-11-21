<?php
namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DateRange extends Constraint
{
    public $message = 'Date range is invalid';
    
    public $startDateField = 'startDate';
    
    public $endDateField = 'endDate';
    
    public $errorPath = 'endDate';
    
    public function validatedBy()
    {
        return 'date_range';
    }
    
    public function getTargets()
    {
        return static::CLASS_CONSTRAINT;
    }
}
