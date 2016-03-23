<?php

namespace Sportnco\RedisORM\Repository;


use Predis\Client;
use Sportnco\RedisORM\Entity\StorageEntity;
use Sportnco\RedisORM\EntityManager\EntityManagerInterface;
use Sportnco\RedisORM\Exception\InvalidArgumentException;
use Sportnco\RedisORM\Exception\InvalidMetadataAnnotationException;
use Sportnco\RedisORM\Exception\RedisORMException;
use Sportnco\RedisORM\KeyBuilder\StorageKeyBuilder;
use Sportnco\RedisORM\Metadata\EntityMetadata;
use Sportnco\RedisORM\MethodCaller\GetterCaller;
use Sportnco\RedisORM\MethodCaller\SetterCaller;

class StorageRepository implements StorageRepositoryInterface
{

    CONST ORDER_BY_VALUE_ID_SEPTARATOR  =   "{ORDER}";

    /**
     * @var StorageKeyBuilder
     */
    protected $storageKeyBuilder;

    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var GetterCaller
     */
    protected $getterCaller;

    /**
     * @var SetterCaller
     */
    protected $setterCaller;

    /**
     * @param StorageKeyBuilder $storageKeyBuilder
     * @param EntityMetadata $entityMetadata
     * @param EntityManagerInterface $entityManager
     * @param GetterCaller $getterCaller
     * @param SetterCaller $setterCaller
     */
    public function __construct(
        StorageKeyBuilder $storageKeyBuilder,
        EntityMetadata $entityMetadata,
        EntityManagerInterface $entityManager,
        GetterCaller $getterCaller,
        SetterCaller $setterCaller
    ){
        $this->storageKeyBuilder = $storageKeyBuilder;
        $this->entityMetadata = $entityMetadata;
        $this->entityManager = $entityManager;
        $this->getterCaller = $getterCaller;
        $this->setterCaller = $setterCaller;
    }

    /**
     * @param bool|false $forceNoPipeline
     * @return Client|
     */
    private function getRedisClient($forceNoPipeline = false)
    {
        if(!$forceNoPipeline && $this->entityManager->isPipelineInitialized()) {
            return $this->entityManager->getPipelineClient();
        }

        return $this->entityManager->getRedisClient();
    }

    private function findByIdString($idString)
    {
        $data = $this
            ->getRedisClient(true)
            ->hgetall($this->storageKeyBuilder->generateKeyData($idString));

        if(count($data) > 0) {
            return $this->entityManager->getSerializer()->fromArray($data, $this->entityMetadata->getEntityClass());
        }

        return null;
    }

    public function find($id)
    {
        if(is_array($id)) {
            ksort($id);
            $idString = $this->buildIdStringFromIdsPropertiesArray($id);
        } else {
            $idString = $this->buildIdStringFromIdsPropertiesArray([$this->entityMetadata->getIdProperty()=>$id]);
        }

        return $this->findByIdString($idString);
    }

    public function findAll($limit=-1, $offset=0)
    {
        $entities = [];
        foreach($this->getRedisClient(true)->lrange($this->storageKeyBuilder->generateKeyIds(), $offset, ($offset+$limit)) as $idString){
            $entities[] = $this->findByIdString($idString);
        }

        return $entities;
    }

    public function findBy(array $criteria)
    {
        if(count($criteria) === 0) {
            throw new InvalidArgumentException('criteria list is empty');
        }
        $indexesKeys = [];
        foreach($criteria as $indexName=>$indexValue) {
            if(!in_array($indexName, $this->entityMetadata->getIndexesProperties())) {
                throw new InvalidArgumentException("'$indexName' index must be handled by '".$this->entityMetadata->getEntityClass()."'");
            }
            $indexesKeys[] = $this->storageKeyBuilder->generateKeyIndexByDirectValue($indexName, $indexValue);
        }
        $entities = [];
        $ids = $this->getRedisClient(true)->sinter($indexesKeys);

        foreach($ids as $id) {
            $entities[] = $this->findByIdString($id);
        }

        return $entities;
    }

    /**
     * @param array $criteria
     * @return int
     * @throws InvalidArgumentException
     */
    public function count(array $criteria = [])
    {
        if(count($criteria) > 0) {
            $indexesKeys = [];
            foreach($criteria as $indexName=>$indexValue) {
                if(!in_array($indexName, $this->entityMetadata->getIndexesProperties())) {
                    throw new InvalidArgumentException("'$indexName' index must be handled by '".$this->entityMetadata->getEntityClass()."'");
                }
                $indexesKeys[] = $this->storageKeyBuilder->generateKeyIndexByDirectValue($indexName, $indexValue);
            }
            return count( $this->getRedisClient(true)->sinter($indexesKeys) );
        } else {
            return $this->getRedisClient(true)->llen($this->storageKeyBuilder->generateKeyIds());
        }
    }

    /**
     * @param $idsProperties
     * @return string
     * @throws InvalidArgumentException
     */
    private function buildIdStringFromIdsPropertiesArray($idsProperties)
    {
        foreach($idsProperties as $idValue) {
            $ids[] = $idValue;
        }

        return implode(':', $ids);
    }

    /**
     * @param $entity
     * @return string
     * @throws InvalidArgumentException
     */
    public function buildIdStringFromEntity($entity){
        $ids = [];
        $idsProperties = $this->entityMetadata->getIdsProperties();
        foreach($idsProperties as $idProperty) {
            $id = $this->getterCaller->call($entity, $idProperty);
            if(is_null($id)) {
                throw new InvalidArgumentException("$idProperty property is tagged as ID, it cannot be null");
            }
            $ids[$idProperty] = $id;
        }

        return $this->buildIdStringFromIdsPropertiesArray($ids);
    }

    public function testEntityType($entity) {
        $class = $this->entityMetadata->getEntityClass();
        if($entity instanceof $class) {
            return;
        } else {
            throw new InvalidArgumentException('This repository managed only entities of class: ' . $class );
        }
    }

    /**
     * @param int $nbIdsToReserve
     * @return array
     * @throws InvalidMetadataAnnotationException
     */
    public function reserveIds($nbIdsToReserve=1) {
        if(!$this->entityMetadata->isAutoIncrement())
        {
            throw new InvalidMetadataAnnotationException('You can reserve id(s) only if your entity has a single entity and if you activate "autoIncrement" option');
        }

        $idsReserved = [];
        for($i=1;$i<=$nbIdsToReserve;$i++) {
            $idsReserved[] = $this->getNextFreeId();
        }

        return $idsReserved;
    }

    public function insert($entity)
    {
        $this->testEntityType($entity);
        if($this->entityMetadata->isAutoIncrement()) {
            //test if ID is null, autoincrement it
            if(is_null($this->getterCaller->call($entity, $this->entityMetadata->getIdProperty()))) {
                $this->setterCaller->call($entity, $this->entityMetadata->getIdProperty(), $this->getNextFreeId());
            }
        } else {
            foreach($this->entityMetadata->getIdsProperties() as $idProperty) {
                if(is_null($this->getterCaller->call($entity, $idProperty))) {
                    throw new InvalidArgumentException(get_class($entity) . ": You can't insert a multi ID entity if you do not set ID.");
                }
            }
        }
        $idString = $this->buildIdStringFromEntity($entity);
        //list of all ids
        $this->getRedisClient()->rpush($this->storageKeyBuilder->generateKeyIds(), [$idString]);
        //set entity data
        $this->getRedisClient()->hmset(
            $this->storageKeyBuilder->generateKeyData($idString),
            $this->entityManager->getSerializer()->toArray($entity)
        );
        $this->createSearchIndexes($entity);
        $this->createOrderbyIndexes($entity);
        $this->createDateIndexes($entity);

        return $entity;
    }

    public function update($entity)
    {
        //todo check if exist before
        $this->getRedisClient()->multi();
        //update data
        $this->getRedisClient()->hmset(
            $this->generateKeyData($entity->getId()),
            $this->entityManager->getSerializer()->toArray($entity)
        );
        $this->removeIndexes($entity);
        $this->createSearchIndexes($entity);
        $this->getRedisClient()->exec();
    }

    /**
     * @param Object $entity
     */
    public function removeIndexes($entity) {
        foreach($this->entityMetadata->getIndexesProperties() as $index) {
            $this->getRedisClient()->srem($this->storageKeyBuilder->generateKeyIndex($entity, $index), $entity->getId());
        }
    }

    /**
     *
     * @param Object $entity
     */
    public function createSearchIndexes($entity) {

        foreach($this->entityMetadata->getIndexesProperties() as $index) {
            $this->getRedisClient()->sadd($this->storageKeyBuilder->generateKeyIndex($entity, $index), [$this->buildIdStringFromEntity($entity)]);
        }
    }

    public function buildOrderByIndexValue($value, $idString) {
        return $value . self::ORDER_BY_VALUE_ID_SEPTARATOR . $idString;
    }

    public function createOrderbyIndexes($entity)
    {
        foreach($this->entityMetadata->getOrderByProperties() as $property) {
            $value = $this->getterCaller->call($entity, $property);
            $this->getRedisClient()->zadd($this->storageKeyBuilder->generateKeyOrderBy($property), [$this->buildOrderByIndexValue($value, $this->buildIdStringFromEntity($entity)) => 0]);
        }
    }

    public function createDateIndexes($entity)
    {
        foreach($this->entityMetadata->getDatesProperties() as $property) {
            $date = $this->getterCaller->call($entity, $property);
            if($date instanceof \DateTimeInterface) {
                $this->getRedisClient()->zadd($this->storageKeyBuilder->generateKeyDateIndex($property), [$this->buildIdStringFromEntity($entity) => $date->getTimestamp()]);
            } else {
               throw new InvalidMetadataAnnotationException("property $property of class " . get_class($entity) . " must be a implementation of DateTimeInterface");
            }
        }
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Exception
     *
     */
    public function remove($id)
    {
        if(!$this->getRedisClient(true)->exists($this->generateKeyData($id)))
        {
            return false;
        }
        $entity = $this->find($id);
        $this->getRedisClient()->multi();

        //remove id in Ids list
        $this->getRedisClient()->lrem($this->generateKeyIds(), 0, $id);
        //remove entity data
        $this->getRedisClient()->del($this->generateKeyData($id));
        //remove id in indexes
        foreach($this->entityIndexes as $index) {
            $keyIndex = $this->generateKeyIndex($entity, $index);
            $this->getRedisClient()->srem($keyIndex, $id);
            //remove index key if useless
            if($this->getRedisClient()->scard($keyIndex) === 0) {
                $this->getRedisClient()->del($keyIndex);
            }
        }
        unset($entity);
        $this->getRedisClient()->exec();

        return true;
    }

    /**
     * increment ID until new ID not used found
     *
     * @return int
     * @throws RedisORMException
     */
    public function getNextFreeId()
    {
        if($this->entityManager->isPipelineInitialized()) {
            throw new RedisORMException('You cannot get next free id while pipeline context is activated');
        }

        $continue = true;
        while($continue) {
            $id = $this
                ->getRedisClient()
                ->incr($this->storageKeyBuilder->generateKeyPrimaryIncr());

            if(!$this->getRedisClient(true)->exists($this->storageKeyBuilder->generateKeyData($id))) {
                $continue = false;
            }
        }

        return $id;
    }
}