<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 15:17
 */

namespace Sportnco\RedisORM\Repository;

use Sportnco\RedisORM\EntityManager\EntityManagerInterface;
use Sportnco\RedisORM\KeyBuilder\StorageKeyBuilder;
use Sportnco\RedisORM\Metadata\EntityMetadata;
use Sportnco\RedisORM\MethodCaller\GetterCaller;
use Sportnco\RedisORM\MethodCaller\SetterCaller;

class Factory
{
    /**
     * @var GetterCaller
     */
    protected $getterCaller;
    /**
     * @var SetterCaller
     */
    protected $setterCaller;
    /**
     * @var string
     */
    protected $keyPrefix;

    /**
     * @param GetterCaller $getterCaller
     * @param SetterCaller $setterCaller
     * @param $keyPrefix
     */
    public function __construct(GetterCaller $getterCaller, SetterCaller $setterCaller, $keyPrefix='')
    {
        $this->getterCaller = $getterCaller;
        $this->setterCaller = $setterCaller;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param EntityMetadata $entityMetadata
     * @return StorageRepositoryInterface
     */
    public function getStorageRepository(EntityManagerInterface $entityManager, EntityMetadata $entityMetadata){
        $storageKeyBuilder = new StorageKeyBuilder($entityMetadata, $this->getterCaller, $this->keyPrefix);

        return new StorageRepository($storageKeyBuilder, $entityMetadata, $entityManager, $this->getterCaller, $this->setterCaller);
    }
}