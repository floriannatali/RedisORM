<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 15:21
 */

namespace Sportnco\RedisORM\EntityManager;

use Predis\Client;
use Predis\Pipeline\Pipeline;
use Sportnco\RedisORM\Exception\RedisORMException;
use Sportnco\RedisORM\Repository\Factory as RepositoryFactory;
use Sportnco\RedisORM\Metadata\Factory as MetadataFactory;

class EntityManager implements EntityManagerInterface
{
    /**
     * @var Client
     */
    protected $redisClient;

    /**
     * @var Pipeline
     */
    protected $redisPipeline;

    /**
     * @var RepositoryFactory
     */
    protected $repositoryFactory;

    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;


    /**
     * @param Client $redisClient
     * @param RepositoryFactory $repositoryFactory
     * @param MetadataFactory $metadataFactory
     */
    public function __construct(
        Client $redisClient,
        RepositoryFactory $repositoryFactory,
        MetadataFactory $metadataFactory
    )
    {
        $this->redisClient = $redisClient;
        $this->repositoryFactory = $repositoryFactory;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * todo
     *
     * @param string $entityName
     * @return \Sportnco\RedisORM\Repository\StorageRepositoryInterface

    public function getQueueRepository($entityName)
    {
        return $this->repositoryFactory->getStorageRepository($this, $entityName);
    }
    */

    /**
     * @param string $entityClass
     * @return \Sportnco\RedisORM\Repository\StorageRepository
     */
    public function getStorageRepository($entityClass)
    {
        return $this
            ->repositoryFactory
            ->getStorageRepository($this, $this->metadataFactory->getEntityMetadata(new \ReflectionClass($entityClass)));
    }

    /**
     * @return Client
     */
    public function getRedisClient()
    {
        return $this->redisClient;
    }

    /**
     * @return Pipeline
     *
     * @throws RedisORMException
     */
    public function getPipelineClient()
    {
        if(is_null($this->redisPipeline)) {
            throw new RedisORMException('You must init pipeline before');
        }

        return $this->redisPipeline;
    }

    public function initPipeline() {
        if($this->redisPipeline != null) {
            throw new RedisORMException('Pipeline already initialized');
        }
        $this->redisPipeline = $this->redisClient->pipeline();
    }

    public function execPipeline() {
        if(is_null($this->redisPipeline)) {
            throw new RedisORMException('You must init pipeline before');
        }

        $report = $this->redisPipeline->execute();
        $this->redisPipeline = null;

        return $report;
    }

    public function isPipelineInitialized() {
        return $this->redisPipeline != null;
    }
}