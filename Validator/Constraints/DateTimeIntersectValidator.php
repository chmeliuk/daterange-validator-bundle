<?php
namespace BestModules\DateRangeValidatorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Doctrine\ORM\EntityManager;
use BestModules\DateRangeValidatorBundle\Validator\Constraints\DateTimeIntersect;

class DateTimeIntersectValidator extends AbstractDateRangeIntersectValidator
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * (StartDate1 <= EndDate2) and (StartDate2 <= EndDate1)
     * 
     * @param object $entity
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$constraint instanceof DateTimeIntersect) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\DateTimeIntersect');
        }
        
        $class = $this->em->getClassMetadata(get_class($entity));
        /*@var $class \Doctrine\Common\Persistence\Mapping\ClassMetadata*/
        
        $errorPath = $this->getErrorPaths($constraint);
        
        foreach ($errorPath as $fieldName) {
            if (!$class->hasField($fieldName) && !is_null($fieldName)) {
                throw new ConstraintDefinitionException(sprintf("The field '%s' is not mapped by Doctrine.", $fieldName));
            }
        }
        
        $fields = $this->getFields($constraint);
        
        switch (false) {
            case $class->hasField($constraint->startDateField):
                throw new ConstraintDefinitionException(sprintf('There isn`t field %s in entity %s', $constraint->startDateField, get_class($entity)));
            case $class->hasField($constraint->endDateField):
                throw new ConstraintDefinitionException(sprintf('There isn`t field %s in entity %s', $constraint->endDateField, get_class($entity)));
        }
        
        $startDate = $class->getFieldValue($entity, $constraint->startDateField);
        
        if (!$startDate instanceof \DateTime) {
            throw new UnexpectedTypeException($entity->getStartDate(), '\DateTime');
        }
        
        $endDate = $class->getFieldValue($entity, $constraint->endDateField);
        
        if (!$endDate instanceof \DateTime) {
            throw new UnexpectedTypeException($endDate, '\DateTime');
        }
        
        $criteria = array();
        
        foreach ($fields as $fieldName) {
            if (!$class->hasField($fieldName) && !$class->hasAssociation($fieldName)) {
                throw new ConstraintDefinitionException(sprintf("The field '%s' is not mapped by Doctrine.", $fieldName));
            }
            
            $criteria[$fieldName] = $class->reflFields[$fieldName]->getValue($entity);
            
            if (!is_null($criteria[$fieldName]) && $class->hasAssociation($fieldName)) {
                $this->em->initializeObject($criteria[$fieldName]);
                
                $relatedClass = $this->em->getClassMetadata($class->getAssociationTargetClass($fieldName));
                $relatedId = $relatedClass->getIdentifierValues($criteria[$fieldName]);
                
                if (count($relatedId) > 1) {
                    throw new ConstraintDefinitionException("Associated entities are not allowed to have more than one identifier");
                }
                
                $criteria[$fieldName] = array_pop($relatedId);
            }
        }
        
        $queryBuilder = $this->em->getRepository(get_class($entity))
            ->createQueryBuilder('dti')
            ->where("dti.{$constraint->startDateField} <= :endDate AND dti.{$constraint->endDateField} >= :startDate")
            ->setParameters(array(
                'startDate' => $class->getFieldValue($entity, $constraint->startDateField),
                'endDate' => $class->getFieldValue($entity, $constraint->endDateField),
            ))
        ;

        foreach ($criteria as $field => $value) {
            $queryBuilder
                ->andWhere("dti.{$field} = :{$field}")
                ->setParameter($field, $value)
            ;
        }

        $result = $queryBuilder->getQuery()->getResult();
        
        $result instanceof \Iterator 
                ? $result->rewind() 
                : reset($result);

        if (count($result) === 0 || count($result) === 1 && $entity === ($result instanceof \Iterator ? $result->current() : current($result))) {
            return;
        }
        
        $names = array();
        
        foreach ($result as $range) {
            // Check if Entity is not flushed
            if ($class->getFieldValue($range, $constraint->startDateField) <= $class->getFieldValue($entity, $constraint->endDateField)
                    && $class->getFieldValue($range, $constraint->endDateField) >= $class->getFieldValue($entity, $constraint->startDateField)
                    && $range !== $entity) {
                $names[] = "{$range->getStartDate()->format('Y-m-d')} - {$range->getEndDate()->format('Y-m-d')}";
            }
        }
        
        if (!empty($names)) {
            foreach ($errorPath as $errorPathItem) {
                $this
                    ->buildViolation(sprintf($constraint->message))
                    ->setParameter('{{ intersects }}', implode(', ', $names))
                    ->atPath($errorPathItem)
                    ->setInvalidValue($criteria[$fields[0]])
                    ->addViolation()
                ;
            }
        }
    }
    
    /**
     * @param \BestModules\DateRangeValidatorBundle\Validator\Constraints\DateTimeIntersect $constraint
     * @return array
     * @throws UnexpectedTypeException
     */
    protected function getFields(DateTimeIntersect $constraint)
    {
        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array or string');
        }
        
        return (array)$constraint->fields;
    }
}
