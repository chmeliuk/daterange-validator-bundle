<?php

namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class DateRangeCollectionIntersect extends AbstractDateRangeConstraint
{
    public function getTargets()
    {
        return static::PROPERTY_CONSTRAINT;
    }
}
