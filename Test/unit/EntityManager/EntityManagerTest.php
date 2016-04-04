<?php


namespace Sportnco\RedisORM\EntityManager;


use Predis\Client;
use Predis\Pipeline\Pipeline;
use Sportnco\RedisORM\Exception\RedisORMException;
use Sportnco\RedisORM\Metadata\EntityMetadata;
use Sportnco\RedisORM\Metadata\Factory as MetadataFactory;
use Sportnco\RedisORM\Repository\Factory as RepoFactory;

class EntityManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    protected $instance;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $redisClient;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repositoryFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $redisPipeline;

    public function setUp()
    {
        $this->redisClient = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repositoryFactory = $this
            ->getMockBuilder(RepoFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataFactory = $this
            ->getMockBuilder(MetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->redisPipeline = $this
            ->getMockBuilder(Pipeline::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new EntityManager(
            $this->redisClient,
            $this->repositoryFactory,
            $this->metadataFactory
        );
    }

    public function testGetStorageRepository()
    {
        $classToTest = get_class($this);

        $metadataObject = $this
            ->getMockBuilder(EntityMetadata::class)
            ->getMock();

        $repositoryObject = $this
            ->getMockBuilder('RepositoryObject')
            ->getMock();

        $this->metadataFactory
            ->expects($this->once())
            ->method('getEntityMetadata')
            ->with($this->isInstanceOf(\ReflectionClass::class))
            ->will($this->returnValue($metadataObject));

        $this->repositoryFactory
            ->expects($this->once())
            ->method('getStorageRepository')
            ->with(
                $this->instance,
                $metadataObject
            )
            ->will($this->returnValue($repositoryObject));

        $this->assertSame($repositoryObject, $this->instance->getStorageRepository($classToTest));
    }

    public function testGetRedisClient()
    {
        $this->assertSame($this->redisClient, $this->instance->getRedisClient());
    }

    public function testGetPipelineClientException()
    {
        $this->setExpectedException(RedisORMException::class);
        $useless = $this->instance->getPipelineClient();
    }

    public function testIsPipelineInitialized()
    {
        $this->assertFalse($this->instance->isPipelineInitialized());
    }

    public function testInitPipelineAndGetPipelineClient()
    {
        $this->redisClient
            ->expects($this->once())
            ->method('pipeline')
            ->will($this->returnValue($this->redisPipeline));

        $this->instance->initPipeline();
        $this->assertSame($this->redisPipeline, $this->instance->getPipelineClient());

        $this->setExpectedException(RedisORMException::class);
        $this->instance->initPipeline();
    }

    public function testExecPipelineException()
    {
        $this->setExpectedException(RedisORMException::class);
        $useless = $this->instance->execPipeline();
    }

    public function testExecPipeline()
    {
        $this->redisClient
            ->expects($this->once())
            ->method('pipeline')
            ->will($this->returnValue($this->redisPipeline));
        $this->instance->initPipeline();

        $report = $this
            ->getMockBuilder('FakeObject')
            ->setMethods(['fakeMethod'])
            ->getMock();

        $this->redisPipeline
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($report));

        $this->assertSame($report, $this->instance->execPipeline());

        $this->setExpectedException(RedisORMException::class);
        $useless = $this->instance->getPipelineClient();
    }
}
