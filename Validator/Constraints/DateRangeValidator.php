<?php
namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Doctrine\ORM\EntityManager;

class DateRangeValidator extends ConstraintValidator
{
    const NOT_MAPPED_FIELD_ERROR = "The fiels '%s' is not mapped by Doctrine.";
    
    /**
     * @var EntityManager
     */
    protected $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * (StartDate >= EndDate)
     * 
     * @param object $entity
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$constraint instanceof DateRange) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '/DateRange');
        }

        $class = $this->em->getClassMetadata(get_class($entity));
        
        // Validate ErrorPath
        $errorPath = $constraint->errorPath;
        
        if (!is_string($errorPath) && !is_null($errorPath)) {
            throw new UnexpectedTypeException($errorPath, 'string or null');
        }
        
        if (!$class->hasField($errorPath)) {
            throw new ConstraintDefinitionException(sprintf(static::NOT_MAPPED_FIELD_ERROR, $errorPath));
        }
        
        // Validate StartDateField
        $startDateField = $constraint->startDateField;
        
        if (!is_string($startDateField)) {
            throw new UnexpectedTypeException($startDateField, 'string');
        }
        
        if (!$class->hasField($startDateField)) {
            throw new ConstraintDefinitionException(sprint(static::NOT_MAPPED_FIELD_ERROR), $startDateField);
        }
        
        // Validate EndDateField
        $endDateField = $constraint->endDateField;
        
        if (!is_string($endDateField)) {
            throw new UnexpectedTypeException($endDateField, 'string');
        }
        
        if (!$class->hasField($endDateField)) {
            throw new ConstraintDefinitionException(sprintf(static::NOT_MAPPED_FIELD_ERROR), $endDateField);
        }
        
        $startDate = $class->getFieldValue($entity, $startDateField);
        $endDate = $class->getFieldValue($entity, $endDateField);

        if (null === $startDate || '' === $startDate || null === $endDate || '' === $endDate) {
            return;
        }
        
        // Validate StartDate
        if (!$startDate instanceof \DateTime) {
            throw new UnexpectedTypeException($startDate, '\DateTime');
        }
        
        // Validate EndDate
        if (!$endDate instanceof \DateTime) {
            throw new UnexpectedTypeException($endDate, '\DateTime');
        }
        
        if ($endDate->getTimestamp() < $startDate->getTimestamp()) {
            $this
                ->buildViolation($constraint->message)
                ->atPath($errorPath)
                ->addViolation()
            ;
        }
    }
}
