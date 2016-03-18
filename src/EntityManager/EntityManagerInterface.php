<?php
namespace Sportnco\RedisORM\EntityManager;


use JMS\Serializer\Serializer;
use Predis\Client;
use Sportnco\RedisORM\Repository\QueueRepositoryInterface;
use Sportnco\RedisORM\Repository\StorageRepositoryInterface;

interface EntityManagerInterface
{
    /**
     * @param string $entityName
     * @return QueueRepositoryInterface
     */
    public function getQueueRepository($entityName);

    /**
     * @param string $entityName
     * @return StorageRepositoryInterface
     */
    public function getStorageRepository($entityName);

    /**
     * @return Client
     */
    public function getRedisClient();

    /**
     * @return Serializer
     */
    public function getSerializer();
}