<?php
namespace BestModules\DateTimeIntersectBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Doctrine\ORM\EntityManager;
use BestModules\DateTimeIntersectBundle\Validator\Constraints\DateTimeIntersect;
use BestModules\DateTimeIntersectBundle\Library\DateTimeRangeInterface;

class DateTimeIntersectValidator extends ConstraintValidator
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
     * @param DateTimeRangeInterface $entity
     * @param \Symfony\Component\Validator\Constraint $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$constraint instanceof DateTimeIntersect) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\DateTimeIntersect');
        }
        
        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }
        
        if (!is_null($constraint->errorPath) && !is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }
        
        if (!$entity->getStartDate() instanceof \DateTime && !is_null($entity->getStartDate())) {
            throw new UnexpectedTypeException($entity->getStartDate(), '\DateTime');
        }
        
        if (!$entity->getEndDate() instanceof \DateTime && !is_null($entity->getEndDate())) {
            throw new UnexpectedTypeException($entity->getEndDate(), '\DateTime');
        }
        
        $fields = (array) $constraint->fields;
        
        if (empty($fields)) {
            throw new ConstraintDefinitionException('At least one field must be defined.');
        }
        
        $class = $this->em->getClassMetadata(get_class($entity));
        /*@var $class \Doctrine\Common\Persistence\Mapping\ClassMetadata*/
        
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

        $repository = $this->em->getRepository(get_class($entity));
        $queryBuilder = $repository
            ->createQueryBuilder('dti')
            ->where('dti.startDate <= :endDate AND dti.endDate >= :startDate')
            ->setParameter('endDate', $entity->getEndDate())
            ->setParameter('startDate', $entity->getStartDate())
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
        
        $errorPath = is_null($constraint->errorPath) ? $fields[0] : $constraint->errorPath;
        
        $names = array();
        
        foreach ($result as $range) {
            if ($range !== $entity) {
                $names[] = "{$range->getStartDate()->format('Y-m-d')} - {$range->getEndDate()->format('Y-m-d')}";
            }
        }

        $this
            ->buildViolation(sprintf($constraint->message))
            ->setParameter('{{ intersects }}', implode(', ', $names))
            ->atPath($errorPath)
            ->setInvalidValue($criteria[$fields[0]])
            ->addViolation()
        ;
    }
}
