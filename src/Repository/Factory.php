<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 15:17
 */

namespace Sportnco\RedisORM\Repository;

use Sportnco\RedisORM\EntityManager\EntityManagerInterface;

class Factory
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param $entityName
     * @return QueueRepositoryInterface
     */
    public function getQueueRepository(EntityManagerInterface $entityManager, $entityName){
        return new QueueRepository($entityManager, $entityName);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param string $entityName
     * @return StorageRepositoryInterface
     */
    public function getStorageRepository(EntityManagerInterface $entityManager, $entityName){
        return new StorageRepository($entityManager, $entityName);
    }

}