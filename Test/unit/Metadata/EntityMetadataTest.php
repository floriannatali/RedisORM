<?php


namespace Sportnco\RedisORM\Metadata;


use Sportnco\RedisORM\Exception\InvalidMetadataAnnotationException;

class EntityMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityMetadata
     */
    protected $instance;

    public function setUp()
    {
        $this->instance = new EntityMetadata();
    }

    public function testSetterGetter()
    {
        $this->assertFalse($this->instance->isAutoIncrement());
        $this->instance->setAutoIncrement(true);
        $this->assertTrue($this->instance->isAutoIncrement());
        $this->instance->setAutoIncrement(false);
        $this->assertFalse($this->instance->isAutoIncrement());

        $this->instance->setEntityClass('The\\Class');
        $this->assertEquals('The\\Class', $this->instance->getEntityClass());

        $this->instance->setEntityName('TheName');
        $this->assertEquals('TheName', $this->instance->getEntityName());

        $this->instance->setIdType(EntityMetadata::ID_TYPE_SINGLE);
        $this->assertEquals(EntityMetadata::ID_TYPE_SINGLE, $this->instance->getIdType());
    }

    public function testAdderGetter()
    {
        $this->assertEquals([], $this->instance->getIdsProperties());
        $this->instance->addIdProperty('idProp1');
        $this->assertEquals(['idProp1'], $this->instance->getIdsProperties());
        $this->instance->addIdProperty('idProp2');
        $this->assertEquals(['idProp1','idProp2'], $this->instance->getIdsProperties());

        $this->assertEquals([], $this->instance->getIndexesProperties());
        $this->instance->addIndexProperty('indexProp1');
        $this->assertEquals(['indexProp1'], $this->instance->getIndexesProperties());
        $this->instance->addIndexProperty('indexProp2');
        $this->assertEquals(['indexProp1','indexProp2'], $this->instance->getIndexesProperties());

        $this->assertEquals([], $this->instance->getOrderByProperties());
        $this->instance->addOrderByProperty('orderProp1');
        $this->assertEquals(['orderProp1'], $this->instance->getOrderByProperties());
        $this->instance->addOrderByProperty('orderProp2');
        $this->assertEquals(['orderProp1','orderProp2'], $this->instance->getOrderByProperties());

        $this->assertEquals([], $this->instance->getDatesProperties());
        $this->instance->addDateProperty('dateProp1');
        $this->assertEquals(['dateProp1'], $this->instance->getDatesProperties());
        $this->instance->addDateProperty('dateProp2');
        $this->assertEquals(['dateProp1','dateProp2'], $this->instance->getDatesProperties());
    }

    public function testGetIdProperty()
    {
        $this->instance->setIdType(EntityMetadata::ID_TYPE_SINGLE);
        $this->instance->addIdProperty('idProp1');
        $this->assertEquals('idProp1', $this->instance->getIdProperty());
        $this->instance->addIdProperty('idProp2');

        $this->setExpectedException(InvalidMetadataAnnotationException::class);
        $useless = $this->instance->getIdProperty();
    }

}
