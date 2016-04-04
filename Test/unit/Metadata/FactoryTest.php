<?php


namespace Sportnco\RedisORM\Metadata;


use Doctrine\Common\Annotations\AnnotationReader;
use Sportnco\RedisORM\Annotation\Date;
use Sportnco\RedisORM\Annotation\Entity;
use Sportnco\RedisORM\Annotation\ID;
use Sportnco\RedisORM\Annotation\Index;
use Sportnco\RedisORM\Annotation\Order;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Factory
     */
    protected $instance;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $annotationReader;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataValidator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $reflexionClassMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    public function setUp()
    {
        $this->annotationReader = $this
            ->getMockBuilder(AnnotationReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataValidator = $this
            ->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata = $this
            ->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reflexionClassMetadata = $this
            ->getMockBuilder(\ReflectionClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new Factory(
            $this->annotationReader,
            $this->metadataValidator
        );
    }

    public function testSetAnnotationProperty()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('addIdProperty')
            ->with('idProperty');

        $this->entityMetadata
            ->expects($this->once())
            ->method('addIndexProperty')
            ->with('indexProperty');

        $this->entityMetadata
            ->expects($this->once())
            ->method('addDateProperty')
            ->with('dateProperty');

        $this->entityMetadata
            ->expects($this->once())
            ->method('addOrderByProperty')
            ->with('orderProperty');

        $methodSetAnnotationProperty = $this->getPrivateMethod(get_class($this->instance), 'setAnnotationProperty');

        $methodSetAnnotationProperty->invokeArgs($this->instance ,[$this->entityMetadata, new ID(), 'idProperty']);
        $methodSetAnnotationProperty->invokeArgs($this->instance ,[$this->entityMetadata, new Index(), 'indexProperty']);
        $methodSetAnnotationProperty->invokeArgs($this->instance ,[$this->entityMetadata, new Order(), 'orderProperty']);
        $methodSetAnnotationProperty->invokeArgs($this->instance ,[$this->entityMetadata, new Date(), 'dateProperty']);
    }


    public function testParsePropertiesAnnotations()
    {
        $idProperty = $this
            ->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $idProperty
            ->expects($this->once())
            ->method('getName')
            ->will(
                $this->returnValue('idPropertyName')
            );

        $this->reflexionClassMetadata
            ->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue(
                [$idProperty]
            ));

        $this->annotationReader
            ->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($idProperty)
            ->will($this->returnValue(
                [new ID()]
            ))
        ;

        $this->entityMetadata
            ->expects($this->once())
            ->method('addIdProperty')
            ->with('idPropertyName');

        $methodParsePropertiesAnnotations = $this->getPrivateMethod(get_class($this->instance), 'parsePropertiesAnnotations');
        $methodParsePropertiesAnnotations->invokeArgs($this->instance, [$this->entityMetadata, $this->reflexionClassMetadata]);
    }


    public function testParseClassAnnotations()
    {
        $annotationEntity = new Entity();
        $annotationEntity->name = 'EntityName';
        $annotationEntity->autoIncrement = true;

        $this->annotationReader
            ->expects($this->once())
            ->method('getClassAnnotations')
            ->with($this->reflexionClassMetadata)
            ->will($this->returnValue(
                [$annotationEntity]
            ));

        $this->entityMetadata
            ->expects($this->once())
            ->method('setEntityName')
            ->with('EntityName');

        $this->entityMetadata
            ->expects($this->once())
            ->method('setAutoIncrement')
            ->with(true);

        $methodParseClassAnnotations = $this->getPrivateMethod(get_class($this->instance), 'parseClassAnnotations');
        $methodParseClassAnnotations->invokeArgs($this->instance, [$this->entityMetadata, $this->reflexionClassMetadata]);
    }



    public function testGetEntityMetadataSingleID()
    {
        $this->metadataValidator
            ->expects($this->once())
            ->method('validate')
            ->with($this->isInstanceOf(EntityMetadata::class))
            ->will($this->returnValue($this->entityMetadata))
            ;

        $this->annotationReader
            ->expects($this->once())
            ->method('getClassAnnotations')
            ->with($this->reflexionClassMetadata)
            ->will($this->returnValue(
                []
            ));

        $idProperty = $this
            ->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $idProperty
            ->expects($this->once())
            ->method('getName')
            ->will(
                $this->returnValue('idPropertyName')
            );

        $this->reflexionClassMetadata
            ->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue(
                [$idProperty]
            ));

        $this->annotationReader
            ->expects($this->once())
            ->method('getPropertyAnnotations')
            ->with($idProperty)
            ->will($this->returnValue(
                [new ID()]
            ))
        ;

        $this->reflexionClassMetadata
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Class\\Name'));

        $this->assertSame($this->entityMetadata ,$this->instance->getEntityMetadata($this->reflexionClassMetadata));
    }

    public function testGetEntityMetadataMultiID()
    {
        $this->metadataValidator
            ->expects($this->once())
            ->method('validate')
            ->with($this->isInstanceOf(EntityMetadata::class))
            ->will($this->returnValue($this->entityMetadata))
        ;

        $this->annotationReader
            ->expects($this->once())
            ->method('getClassAnnotations')
            ->with($this->reflexionClassMetadata)
            ->will($this->returnValue(
                []
            ));

        $idProperty1 = $this
            ->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $idProperty2 = $this
            ->getMockBuilder(\ReflectionProperty::class)
            ->disableOriginalConstructor()
            ->getMock();

        $idProperty1
            ->expects($this->once())
            ->method('getName')
            ->will(
                $this->returnValue('idPropertyName1')
            );

        $idProperty2
            ->expects($this->once())
            ->method('getName')
            ->will(
                $this->returnValue('idPropertyName2')
            );

        $this->reflexionClassMetadata
            ->expects($this->once())
            ->method('getProperties')
            ->will($this->returnValue(
                [$idProperty1, $idProperty2]
            ));

        $this->annotationReader
            ->expects($this->exactly(2))
            ->method('getPropertyAnnotations')
            ->withConsecutive([$idProperty1],[$idProperty2])
            ->will(
                $this->onConsecutiveCalls([new ID()],[new ID()])
            );
        ;

        $this->reflexionClassMetadata
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Class\\Name'));

        $this->assertSame($this->entityMetadata ,$this->instance->getEntityMetadata($this->reflexionClassMetadata));
    }

    /**
     * getPrivateMethod
     *
     * @author	Joe Sexton <joe@webtipblog.com>
     * @param 	string $className
     * @param 	string $methodName
     * @return	\ReflectionMethod
     */
    public function getPrivateMethod( $className, $methodName ) {
        $reflector = new \ReflectionClass( $className );
        $method = $reflector->getMethod( $methodName );
        $method->setAccessible( true );

        return $method;
    }
}
