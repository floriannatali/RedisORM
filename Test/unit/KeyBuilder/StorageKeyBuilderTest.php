<?php


namespace Sportnco\RedisORM\KeyBuilder;


use Sportnco\RedisORM\Exception\InvalidArgumentException;
use Sportnco\RedisORM\Metadata\EntityMetadata;
use Sportnco\RedisORM\MethodCaller\GetterCaller;

class StorageKeyBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StorageKeyBuilder
     */
    protected $instance;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $getterCaller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entity;

    protected $prefix='prefix:';
    protected $entityName='Entity';
    protected $indexesProperties=['property1'];
    protected $orderByProperties=['property2'];
    protected $datesProperties=['property3'];

    public function setUp()
    {
        $this->entity = $this
            ->getMockBuilder('Entity')
            ->setMethods(['getProperty1','setProperty1','getProperty2','setProperty2'])
            ->getMock();

        $this->entityMetadata = $this
            ->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getterCaller = $this
            ->getMockBuilder(GetterCaller::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new StorageKeyBuilder(
            $this->entityMetadata,
            $this->getterCaller,
            $this->prefix
        );
    }

    public function testGenerateKeyPrimaryIncr()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $expectedKey = str_replace(
            array('%prefix%', '%entityName%'),
            array($this->prefix, $this->entityName),
            StorageKeyBuilder::KEY_PATTERN_PRIMARY_INCR
        );

        $this->assertSame($expectedKey, $this->instance->generateKeyPrimaryIncr());
    }

    public function testGenerateKeyData()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $idString = "1";
        $expectedKey = str_replace(
            array('%prefix%', '%entityName%', '%entityId%'),
            array($this->prefix, $this->entityName, $idString),
            StorageKeyBuilder::KEY_PATTERN_DATA
        );

        $this->assertSame($expectedKey, $this->instance->generateKeyData($idString));
    }

    public function testGenerateKeyIds()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $expectedKey = str_replace(
            array('%prefix%', '%entityName%'),
            array($this->prefix, $this->entityName),
            StorageKeyBuilder::KEY_PATTERN_IDS
        );

        $this->assertSame($expectedKey, $this->instance->generateKeyIds());
    }

    public function testGenerateKeyIndexException()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getIndexesProperties')
            ->will($this->returnValue($this->indexesProperties));

        $this->entityMetadata
            ->expects($this->never())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $this->setExpectedException(InvalidArgumentException::class);
        $useless = $this->instance->generateKeyIndex($this->entity, 'unknownIndex');
    }

    public function testGenerateKeyIndex()
    {
        $indexName = 'property1';
        $indexValue = 'propertyValue';

        $this->entityMetadata
            ->expects($this->once())
            ->method('getIndexesProperties')
            ->will($this->returnValue($this->indexesProperties));

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $this->getterCaller
            ->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo($this->entity),
                $this->equalTo($indexName)
            )
            ->will($this->returnValue($indexValue));

        $expectedKey = str_replace(
            ['%prefix%', '%entityName%', '%indexName%', '%indexValue%'],
            [$this->prefix, $this->entityName, $indexName, $indexValue],
            StorageKeyBuilder::KEY_PATTERN_INDEX
        );
        $this->assertSame($expectedKey, $this->instance->generateKeyIndex($this->entity, $indexName));
    }

    public function testGenerateKeyIndexByDirectValueException()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getIndexesProperties')
            ->will($this->returnValue($this->indexesProperties));

        $this->entityMetadata
            ->expects($this->never())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $this->setExpectedException(InvalidArgumentException::class);
        $useless = $this->instance->generateKeyIndexByDirectValue('unknownIndex', 'uselessValue');
    }

    public function testGenerateKeyIndexByDirectValue()
    {
        $indexName = 'property1';
        $indexValue = 'propertyValue';

        $this->entityMetadata
            ->expects($this->once())
            ->method('getIndexesProperties')
            ->will($this->returnValue($this->indexesProperties));

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $expectedKey = str_replace(
            ['%prefix%', '%entityName%', '%indexName%', '%indexValue%'],
            [$this->prefix, $this->entityName, $indexName, $indexValue],
            StorageKeyBuilder::KEY_PATTERN_INDEX
        );
        $this->assertSame($expectedKey, $this->instance->generateKeyIndexByDirectValue($indexName, $indexValue));
    }

    public function testGenerateKeyOrderByException()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getOrderByProperties')
            ->will($this->returnValue($this->orderByProperties));

        $this->entityMetadata
            ->expects($this->never())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $this->setExpectedException(InvalidArgumentException::class);
        $useless = $this->instance->generateKeyOrderBy('unknownIndex');
    }

    public function testGenerateKeyOrderBy()
    {
        $indexName = 'property2';

        $this->entityMetadata
            ->expects($this->once())
            ->method('getOrderByProperties')
            ->will($this->returnValue($this->orderByProperties));

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $expectedKey = str_replace(
            ['%prefix%', '%entityName%', '%orderByName%'],
            [$this->prefix, $this->entityName, $indexName],
            StorageKeyBuilder::KEY_PATTERN_ORDER_BY
        );
        $this->assertSame($expectedKey, $this->instance->generateKeyOrderBy($indexName));
    }

    public function testGenerateKeyDateIndexException()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getDatesProperties')
            ->will($this->returnValue($this->datesProperties));

        $this->entityMetadata
            ->expects($this->never())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $this->setExpectedException(InvalidArgumentException::class);
        $useless = $this->instance->generateKeyDateIndex('unknownIndex');
    }

    public function testGenerateKeyDateIndex()
    {
        $indexName = 'property3';

        $this->entityMetadata
            ->expects($this->once())
            ->method('getDatesProperties')
            ->will($this->returnValue($this->datesProperties));

        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $expectedKey = str_replace(
            ['%prefix%', '%entityName%', '%indexName%'],
            [$this->prefix, $this->entityName, $indexName],
            StorageKeyBuilder::KEY_PATTERN_DATE_INDEX
        );
        $this->assertSame($expectedKey, $this->instance->generateKeyDateIndex($indexName));
    }

    public function testGenerateKeyOrderedIds()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($this->entityName));

        $expectedKey = str_replace(
            array('%prefix%', '%entityName%'),
            array($this->prefix, $this->entityName),
            StorageKeyBuilder::KEY_PATTERN_ORDERED_IDS
        );

        $this->assertSame($expectedKey, $this->instance->generateKeyOrderedIds());
    }
}
