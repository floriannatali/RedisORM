<?php
namespace Sportnco\RedisORM\EntityManager;


use Predis\Client;
use Predis\Pipeline\Pipeline;
use Sportnco\RedisORM\Repository\StorageRepositoryInterface;

interface EntityManagerInterface
{
    /**
     * @param string $entityClass
     * @return StorageRepositoryInterface
     */
    public function getStorageRepository($entityClass);

    /**
     * @return Client
     */
    public function getRedisClient();

    /**
     * @return Pipeline
     */
    public function getPipelineClient();

    /**
     * void
     */
    public function initPipeline();

    /**
     * void
     */
    public function execPipeline();

    /**
     * @return boolean
     */
    public function isPipelineInitialized();
}