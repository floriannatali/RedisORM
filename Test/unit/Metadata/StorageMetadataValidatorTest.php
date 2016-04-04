<?php


namespace Sportnco\RedisORM\Metadata;


use Sportnco\RedisORM\Exception\InvalidMetadataAnnotationException;

class StorageMetadataValidatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    /**
     * @var StorageMetadataValidator
     */
    protected $instance;

    public function setUp(){
        $this->entityMetadata = $this
            ->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new StorageMetadataValidator();
    }

    public function testValidateBadEntityNameException()
    {
        $this->entityMetadata
            ->expects($this->never())
            ->method('getIdsProperties');

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue('ç)à&ç="à"&"'));

        $this->setExpectedException(InvalidMetadataAnnotationException::class);
        $this->instance->validate($this->entityMetadata);
    }

    public function testValidateNoIdPropertyException()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getIdsProperties')
            ->will($this->returnValue([]))
        ;

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue('GoodEntityName'));

        $this->setExpectedException(InvalidMetadataAnnotationException::class);
        $this->instance->validate($this->entityMetadata);
    }

    public function testValidateAutoIncrementWithMoreThanOneIdPropertyException()
    {
        $this->entityMetadata
            ->expects($this->exactly(2))
            ->method('getIdsProperties')
            ->will($this->returnValue(['idProp1', 'idProp2']))
        ;

        $this->entityMetadata
            ->expects($this->once())
            ->method('isAutoIncrement')
            ->will($this->returnValue(true))
        ;

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue('GoodEntityName'));

        $this->setExpectedException(InvalidMetadataAnnotationException::class);
        $this->instance->validate($this->entityMetadata);
    }

    public function testValidate()
    {
        $this->entityMetadata
            ->expects($this->exactly(2))
            ->method('getIdsProperties')
            ->will($this->returnValue(['idProp1']))
        ;

        $this->entityMetadata
            ->expects($this->never())
            ->method('isAutoIncrement')
        ;

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue('GoodEntityName'));

        $this->assertSame($this->entityMetadata, $this->instance->validate($this->entityMetadata));
    }
}
