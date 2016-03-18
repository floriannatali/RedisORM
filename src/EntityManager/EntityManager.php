<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 15:21
 */

namespace Sportnco\RedisORM\EntityManager;

use JMS\Serializer\Serializer;
use Predis\Client;
use Sportnco\RedisORM\Repository\Factory;

class EntityManager implements EntityManagerInterface
{
    /**
     * @var Client
     */
    protected $redisClient;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var Factory
     */
    protected $repositoryFactory;

    /**
     * EntityManager constructor.
     *
     * @param Client $redisClient
     * @param Factory $repositoryFactory
     */
    public function __construct(Client $redisClient, Factory $repositoryFactory, Serializer $serializer)
    {
        $this->redisClient = $redisClient;
        $this->repositoryFactory = $repositoryFactory;
        $this->serializer = $serializer;
    }

    /**
     * @param string $entityName
     * @return \Sportnco\RedisORM\Repository\StorageRepositoryInterface
     */
    public function getQueueRepository($entityName)
    {
        return $this->repositoryFactory->getStorageRepository($this, $entityName);
    }

    /**
     * @param string $entityName
     * @return \Sportnco\RedisORM\Repository\StorageRepositoryInterface
     */
    public function getStorageRepository($entityName)
    {
        return $this->repositoryFactory->getStorageRepository($this, $entityName);
    }

    /**
     * @return Client
     */
    public function getRedisClient()
    {
        return $this->redisClient;
    }

    /**
     * @return Serializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }
}