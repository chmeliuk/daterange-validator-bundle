<?php
namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
class DateTimeIntersect extends AbstractDateRangeConstraint
{
    public $fields;
    
    public function validatedBy()
    {
        return 'datetime_intersect';
    }
    
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
