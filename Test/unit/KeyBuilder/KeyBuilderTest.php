<?php


namespace Sportnco\RedisORM\KeyBuilder;


use Sportnco\RedisORM\Metadata\EntityMetadata;
use Sportnco\RedisORM\MethodCaller\GetterCaller;

class KeyBuilderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var KeyBuilder
     */
    protected $instance;

    /**
     * @var GetterCaller
     */
    protected $getterCaller;

    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    protected $prefix='prefix:';

    public function setUp()
    {
        $this->entityMetadata = $this
            ->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getterCaller = $this
            ->getMockBuilder(GetterCaller::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new KeyBuilder(
            $this->entityMetadata,
            $this->getterCaller,
            $this->prefix
        );
    }

    public function testGetterSetterEntityMetadata()
    {
        $this->assertSame($this->entityMetadata, $this->instance->getEntityMetadata());
        $otherEntityMetadata = $this
            ->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertSame($this->instance ,$this->instance->setEntityMetadata($otherEntityMetadata));
        $this->assertSame($otherEntityMetadata, $this->instance->getEntityMetadata());
    }

    public function testGetterSetterPrefix()
    {
        $this->assertEquals($this->prefix, $this->instance->getPrefix());
        $otherPrefix = "other_prefix";
        $this->assertSame($this->instance ,$this->instance->setPrefix($otherPrefix));
        $this->assertEquals($otherPrefix, $this->instance->getPrefix());
    }

}
