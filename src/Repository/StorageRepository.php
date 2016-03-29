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
        $serializedobject = $this
            ->getRedisClient(true)
            ->get($this->storageKeyBuilder->generateKeyData($idString));

        if(!is_null($serializedobject)) {
            return unserialize($serializedobject);
        }

        return null;
    }

    public function find($id)
    {
        $idString = $this->getIdString($id);

        return $this->findByIdString($idString);
    }

    public function findIdsStringFromIdOrder($sort,$limit,$offset) {
        if(strtoupper($sort)=="DESC"){
            return $this->getRedisClient(true)->zrevrangebyscore($this->storageKeyBuilder->generateKeyIds(), '+inf','-inf', ['LIMIT'=>['COUNT'=>$limit, 'OFFSET'=>$offset]]);
        }else{
            return $this->getRedisClient(true)->zrangebyscore($this->storageKeyBuilder->generateKeyIds(),'-inf', '+inf', ['LIMIT'=>['COUNT'=>$limit, 'OFFSET'=>$offset]]);
        }
    }

    protected function findIdsStringFromDateIndex($property,$sort,$limit,$offset) {
        if(strtoupper($sort)=="DESC"){
            return $this->getRedisClient(true)->zrevrangebyscore($this->storageKeyBuilder->generateKeyDateIndex($property), '+inf','-inf', ['LIMIT'=>['COUNT'=>$limit, 'OFFSET'=>$offset]]);
        }else{
            return $this->getRedisClient(true)->zrangebyscore($this->storageKeyBuilder->generateKeyDateIndex($property),'-inf', '+inf', ['LIMIT'=>['COUNT'=>$limit, 'OFFSET'=>$offset]]);
        }
    }

    protected function findIdsStringFromOrderByIndex($property,$sort,$limit,$offset) {

        if(strtoupper($sort)=="DESC"){
            $ids =  $this->getRedisClient(true)->zrevrangebylex($this->storageKeyBuilder->generateKeyOrderBy($property), '+','-', ['LIMIT'=>['COUNT'=>$limit, 'OFFSET'=>$offset]]);
        }else{
            $ids =  $this->getRedisClient(true)->zrangebylex($this->storageKeyBuilder->generateKeyOrderBy($property),'-', '+', ['LIMIT'=>['COUNT'=>$limit, 'OFFSET'=>$offset]]);
        }
        foreach($ids as &$id) {
            $id = $this->getIdStringFromOrderByIndexValue($id);
        }

        return $ids;
    }

    public function findAll($sort="ASC", $limit=-1, $offset=0, $orderBy=null)
    {
        $entities = [];
        if($orderBy===null){
            $idsString = $this->findIdsStringFromIdOrder($sort,$limit,$offset);
        } else if(in_array($orderBy, $this->entityMetadata->getOrderByProperties())){
            $idsString = $this->findIdsStringFromOrderByIndex($orderBy, $sort,$limit,$offset);
        }else if(in_array($orderBy, $this->entityMetadata->getDatesProperties())){
            $idsString = $this->findIdsStringFromDateIndex($orderBy, $sort,$limit,$offset);
        }else {
            throw new InvalidArgumentException("'$orderBy' order by index not found");
        }

        foreach($idsString as $idString){
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
            return $this->getRedisClient(true)->zcount($this->storageKeyBuilder->generateKeyIds(), '-inf', '+inf');
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
        $idString = $this->getIdString($entity);
        //sorted list of all ids
        $this->getRedisClient()->zadd($this->storageKeyBuilder->generateKeyIds(), [$idString=>(int)$idString]);
        //set entity data
        $this->getRedisClient()->set(
            $this->storageKeyBuilder->generateKeyData($idString),
            serialize($entity)
        );
        $this->createSearchIndexes($entity);
        $this->createOrderbyIndexes($entity);
        $this->createDateIndexes($entity);

        return $entity;
    }

    /**
     *
     * @param Object $entity
     */
    public function createSearchIndexes($entity) {

        foreach($this->entityMetadata->getIndexesProperties() as $index) {
            $this->getRedisClient()->sadd($this->storageKeyBuilder->generateKeyIndex($entity, $index), [$this->getIdString($entity)]);
        }
    }

    public function buildOrderByIndexValue($value, $idString) {
        return $value . self::ORDER_BY_VALUE_ID_SEPTARATOR . $idString;
    }

    public function getIdStringFromOrderByIndexValue($indexValue) {
        $tmp = explode(self::ORDER_BY_VALUE_ID_SEPTARATOR, $indexValue);
        if(count($tmp) != 2) {
            throw new InvalidArgumentException('not valid index value');
        }

        return $tmp[(count($tmp)-1)];
    }

    public function createOrderbyIndexes($entity)
    {
        foreach($this->entityMetadata->getOrderByProperties() as $property) {
            $value = $this->getterCaller->call($entity, $property);
            $this->getRedisClient()->zadd($this->storageKeyBuilder->generateKeyOrderBy($property), [$this->buildOrderByIndexValue($value, $this->getIdString($entity)) => 0]);
        }
    }

    public function createDateIndexes($entity)
    {
        foreach($this->entityMetadata->getDatesProperties() as $property) {
            $date = $this->getterCaller->call($entity, $property);
            if($date instanceof \DateTimeInterface) {
                $this->getRedisClient()->zadd($this->storageKeyBuilder->generateKeyDateIndex($property), [$this->getIdString($entity) => $date->getTimestamp()]);
            } else {
               throw new InvalidMetadataAnnotationException("property $property of class " . get_class($entity) . " must be an implementation of DateTimeInterface");
            }
        }
    }

    public function removeIndexes($entity)
    {
        foreach($this->entityMetadata->getIndexesProperties() as $index) {
            $this->getRedisClient()->srem($this->storageKeyBuilder->generateKeyIndex($entity, $index),  $this->getIdString($entity));
        }
    }

    public function removeOrderByIndexes($entity)
    {
        foreach($this->entityMetadata->getOrderByProperties() as $property) {
            $value = $this->getterCaller->call($entity, $property);
            $this->getRedisClient()->zrem($this->storageKeyBuilder->generateKeyOrderBy($property), $this->buildOrderByIndexValue($value, $this->getIdString($entity)));
        }
    }

    public function removeDateIndexes($entity)
    {
        foreach($this->entityMetadata->getDatesProperties() as $property) {
            $this->getRedisClient()->zrem($this->storageKeyBuilder->generateKeyDateIndex($property), $this->getIdString($entity));
        }
    }


    public function getIdString($mixedId)
    {
        $class = $this->entityMetadata->getEntityClass();
        if(is_array($mixedId)) {
            ksort($mixedId);
            $ids = [];
            foreach($mixedId as $idProperty => $idValue) {
                if(in_array($idProperty, $this->entityMetadata->getIdsProperties())) {
                    $ids[] =  $idValue;
                } else {
                    throw new InvalidArgumentException("$class: Invalid ID property: $idProperty");
                }
            }
            return implode(":", $ids);

        } else if($mixedId instanceof $class) {
            $ids = [];
            $idsProperties = $this->entityMetadata->getIdsProperties();
            sort($idsProperties);
            foreach($idsProperties as $idProperty) {
                $id = $this->getterCaller->call($mixedId, $idProperty);
                if(is_null($id)) {
                    throw new InvalidArgumentException("$idProperty property is tagged as ID, it cannot be null");
                }
                $ids[] = $id;
            }
            return implode(":", $ids);

        } else if(is_object($mixedId)){
            throw new InvalidArgumentException('The "Id" parameter must be valid (direct ID value OR an array of Ids if multiple IDs entity OR the entity to delete)');
        } else {
            return $mixedId;
        }
    }

    /**
     * @param mixed $id
     * @return bool true if entity found and removed, false if entity not found
     * @throws \Exception
     *
     */
    public function remove($id)
    {
        $entity = $this->find($id);
        if(is_null($entity)) {
            return false;
        }
        $idString = $this->getIdString($entity);
        //remove id in Ids sorted list
        $this->getRedisClient()->zrem($this->storageKeyBuilder->generateKeyIds(),$idString);
        //remove entity data
        $this->getRedisClient()->del($this->storageKeyBuilder->generateKeyData($idString));
        //remove indexes
        $this->removeIndexes($entity);
        $this->removeOrderByIndexes($entity);
        $this->removeDateIndexes($entity);
        unset($entity);

        return true;
    }


    public function update($entity)
    {
        $this->testEntityType($entity);
        $idString = $this->getIdString($entity);
        $oldEntity = $this->find($entity);

        //set entity data
        $this->getRedisClient()->set(
            $this->storageKeyBuilder->generateKeyData($idString),
            serialize($entity)
        );

        $this->updateSearchIndexes($oldEntity, $entity);
        $this->updateOrderByIndexes($oldEntity, $entity);
        $this->updateDateIndexes($oldEntity, $entity);
    }

    public function updateSearchIndexes($oldEntity, $newEntity)
    {
        foreach($this->entityMetadata->getIndexesProperties() as $index) {
            $oldIndexValue = $this->getterCaller->call($oldEntity, $index);
            $newIndexValue = $this->getterCaller->call($newEntity, $index);
            //rebuild index only if needed
            if($oldIndexValue != $newIndexValue) {
                $this->getRedisClient()->srem($this->storageKeyBuilder->generateKeyIndex($oldEntity, $index),  $this->getIdString($oldEntity));
                $this->getRedisClient()->sadd($this->storageKeyBuilder->generateKeyIndex($newEntity, $index), [$this->getIdString($newEntity)]);
            }
        }
    }

    public function updateOrderByIndexes($oldEntity, $newEntity)
    {
        foreach($this->entityMetadata->getOrderByProperties() as $property) {
            $oldValue = $this->getterCaller->call($oldEntity, $property);
            $newValue = $this->getterCaller->call($newEntity, $property);
            if($oldValue != $newValue) {
                $this->getRedisClient()->zrem($this->storageKeyBuilder->generateKeyOrderBy($property), $this->buildOrderByIndexValue($oldValue, $this->getIdString($oldEntity)));
                $this->getRedisClient()->zadd($this->storageKeyBuilder->generateKeyOrderBy($property), [$this->buildOrderByIndexValue($newValue, $this->getIdString($newEntity)) => 0]);
            }
        }
    }

    public function updateDateIndexes($oldEntity, $newEntity)
    {
        foreach($this->entityMetadata->getDatesProperties() as $property) {
            $oldDate = $this->getterCaller->call($oldEntity, $property);
            $newDate = $this->getterCaller->call($newEntity, $property);
            if($newDate instanceof \DateTimeInterface) {
                if($newDate->getTimestamp() != $oldDate->getTimestamp()) {
                    $this->getRedisClient()->zrem($this->storageKeyBuilder->generateKeyDateIndex($property), $this->getIdString($oldEntity));
                    $this->getRedisClient()->zadd($this->storageKeyBuilder->generateKeyDateIndex($property), [$this->getIdString($newEntity) => $newDate->getTimestamp()]);
                }
            } else {
                throw new InvalidMetadataAnnotationException("property $property of class " . get_class($newEntity) . " must be an implementation of DateTimeInterface");
            }
        }
    }

    /**
     * increment ID until new ID not used found
     *
     * @return int
     * @throws RedisORMException
     */
    public function getNextFreeId()
    {
        $continue = true;
        while($continue) {
            $id = $this
                ->getRedisClient(true)
                ->incr($this->storageKeyBuilder->generateKeyPrimaryIncr());

            if(!$this->getRedisClient(true)->exists($this->storageKeyBuilder->generateKeyData($id))) {
                $continue = false;
            }
        }

        return $id;
    }
}