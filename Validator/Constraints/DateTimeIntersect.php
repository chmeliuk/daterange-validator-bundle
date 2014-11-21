<?php
namespace BestModules\DateTimeIntersectBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
class DateTimeIntersect extends Constraint
{
    public $message = 'Dates are intersected';
    
    public $fields;
    
    public $errorPath = array('startDate', 'endDate');
    
    public $startDateField = 'startDate';
    
    public $endDateField = 'endDate';


    public function validatedBy()
    {
        return 'datetime_intersect';
    }
    
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
