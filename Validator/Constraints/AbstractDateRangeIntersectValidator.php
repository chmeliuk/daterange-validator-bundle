<?php

namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

abstract class AbstractDateRangeIntersectValidator extends ConstraintValidator
{
    protected function validateRanges($ranges, AbstractDateRangeConstraint $constraint)
    {
        // Check value
        if (!is_array($ranges) && !$ranges instanceof \Traversable) {
            throw new UnexpectedTypeException($ranges, 'array or \Traversable or \ArrayAccess');
        }
        
        $checkedRanges = [];
        $invalidRanges = [];
        
        foreach ($ranges as $range) {
            switch (false) {
                case property_exists($range, $constraint->startDateField):
                    throw new ConstraintDefinitionException(sprintf('There isn`t field %s in entity %s', $constraint->startDateField, get_class($range)));
                case property_exists($range, $constraint->endDateField):
                    throw new ConstraintDefinitionException(sprintf('There isn`t field %s in entity %s', $constraint->endDateField, get_class($range)));
            }
            
            $class = new \ReflectionClass($range);

            $startFieldProperty = $class->getProperty($constraint->startDateField);
            $endFieldProperty = $class->getProperty($constraint->endDateField);

            $startFieldProperty->setAccessible(true);
            $endFieldProperty->setAccessible(true);

            $startDate = $startFieldProperty->getValue($range);
            $endDate = $endFieldProperty->getValue($range);
            
            foreach ($checkedRanges as $checkedRange) {
                if ($checkedRange['start'] <= $endDate && $checkedRange['end'] >= $startDate) {
                    $invalidRanges[] = sprintf('%s - %s', $checkedRange['start']->format('Y-m-d'), $checkedRange['end']->format('Y-m-d'));
                    $invalidRanges[] = sprintf('%s - %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                }
            }
            
            $checkedRanges[] = [
                'start' => $startDate,
                'end' => $endDate,
            ];
        }
        
        return array_unique($invalidRanges);
    }
    
    /**
     * @param \BestModules\DateRangeValidatorBundle\Validator\Constraints\AbstractDateRangeConstraint $constraint
     * @return array
     * @throws UnexpectedTypeException
     */
    protected function getErrorPaths(AbstractDateRangeConstraint $constraint)
    {
        $result = array();
        
        switch (true) {
            case is_null($constraint->errorPath):
                $result[] = null;
                break;
            case is_string($constraint->errorPath):
                $result[] = $constraint->errorPath;
                break;
            case is_array($constraint->errorPath):
                $result = $constraint->errorPath;
                break;
            default:
                throw new UnexpectedTypeException($constraint->errorPath, 'string, array or null');
        }
        
        return $result;
    }
}
