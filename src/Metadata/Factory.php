<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 18/03/16
 * Time: 17:08
 */

namespace Sportnco\RedisORM\Metadata;


use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\Annotation\Type;
use Sportnco\RedisORM\Annotation\Date;
use Sportnco\RedisORM\Annotation\Entity;
use Sportnco\RedisORM\Annotation\ID;
use Sportnco\RedisORM\Annotation\Index;
use Sportnco\RedisORM\Annotation\Order;
use Sportnco\RedisORM\Annotation\RedisORM;

class Factory
{
    /**
     * @var AnnotationReader
     */
    protected $annotationReader;

    /**
     * @var ValidatorInterface
     */
    protected $metadataValidator;

    /**
     * @param AnnotationReader $annotationReader
     * @param ValidatorInterface $metadataValidator
     */
    public function __construct(AnnotationReader $annotationReader, ValidatorInterface $metadataValidator)
    {
        $this->annotationReader = $annotationReader;
        $this->metadataValidator = $metadataValidator;
    }

    /**
     * @param string $entityClass
     * @return EntityMetadata
     */
    public function getEntityMetadata($entityClass){
        $entityMetadata = new EntityMetadata();
        $reflectionObj = new \ReflectionClass($entityClass);
        $entityMetadata->setEntityClass($entityClass);

        $this->parseClassAnnotations($entityMetadata, $reflectionObj);
        $this->parsePropertiesAnnotations($entityMetadata, $reflectionObj);

        if(count($entityMetadata->getIdsProperties()) > 1) {
            $entityMetadata->setIdType($entityMetadata::ID_TYPE_MULTIPLE);
        } else if(count($entityMetadata->getIdsProperties()) == 1) {
            $entityMetadata->setIdType($entityMetadata::ID_TYPE_SINGLE);
        }

        return $this->metadataValidator->validate($entityMetadata);
    }

    private function parseClassAnnotations(EntityMetadata $entityMetadata, \ReflectionClass $reflectionObj)
    {
        $classAnnotations = $this->annotationReader->getClassAnnotations($reflectionObj);
        foreach($classAnnotations as $classAnnot) {
            if($classAnnot instanceof Entity) {
                $entityMetadata->setEntityName($classAnnot->name);
                if(true === $classAnnot->autoIncrement) {
                    $entityMetadata->setAutoIncrement(true);
                }
            }
        }
    }

    private function parsePropertiesAnnotations(EntityMetadata $entityMetadata, \ReflectionClass $reflectionObj)
    {
        foreach($reflectionObj->getProperties() as $property) {
            $jmsTypeSet = false;

            foreach($this->annotationReader->getPropertyAnnotations($property) as $annotation) {
                if(is_subclass_of($annotation, RedisORM::class)){
                    $this->setAnnotationProperty($entityMetadata, $annotation, $property->getName());
                }

                if($annotation instanceof Type){
                    $jmsTypeSet = true;
                }
            }

            if(!$jmsTypeSet) {
                throw new \InvalidArgumentException(Sport::class . ': JMS type is missing for serialization for property: '. $property->getName());
            }
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @param RedisORM $annotation
     * @param string $propertyname
     */
    private function setAnnotationProperty(EntityMetadata $entityMetadata ,RedisORM $annotation, $propertyname) {
        switch(get_class($annotation)) {
            case ID::class:
                $entityMetadata->addIdProperty($propertyname);
            break;
            case Index::class:
                $entityMetadata->addIndexProperty($propertyname);
            break;
            case Date::class:
                $entityMetadata->addDateProperty($propertyname);
            break;
            case Order::class:
                $entityMetadata->addOrderByProperty($propertyname);
            break;
        }
    }
}