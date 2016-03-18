<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 15:19
 */

namespace Sportnco\RedisORM\Repository;


use Predis\Client;
use Sportnco\RedisORM\Entity\StorageEntity;
use Sportnco\RedisORM\Entity\StorageEntityInterface;
use Sportnco\RedisORM\EntityManager\EntityManagerInterface;
use Sportnco\RedisORM\Exception\EntityAlreadyExistException;
use Sportnco\RedisORM\Exception\EntityNotFoundException;

class StorageRepository extends AbstractRepository implements StorageRepositoryInterface
{

    CONST GLOBAL_PREFIX             =   "model";
    CONST KEY_PATTERN_PRIMARY_INCR  =   "%prefix%:%entityName%:primary-incr";
    CONST KEY_PATTERN_DATA          =   "%prefix%:%entityName%:data:%entityId%";
    CONST KEY_PATTERN_INDEX         =   "%prefix%:%entityName%:index:%indexName%:%indexValue%";
    CONST KEY_PATTERN_IDS           =   "%prefix%:%entityName%:ids";

    CONST DEFAULT_SORT              =   "ASC";
    CONST DEFAULT_OFFSET            =   0;
    CONST DEFAULT_LIMIT             =   100;

    /**
     * @var string
     */
    protected $entityTableName;

    /**
     * @var string
     */
    protected $entityIndexes;

    public function __construct(EntityManagerInterface $entityManager, $entityName)
    {
        parent::__construct($entityManager, $entityName);

        $this->entityTableName = call_user_func($entityName . '::getTableName');
        $this->entityIndexes = call_user_func($entityName . '::getIndexes');
    }


    public function find($id)
    {
        $data = $this
            ->redisClient
            ->hgetall($this->generateKeyData($id));

        if(count($data) > 0) {
            return $this->entityManager->getSerializer()->fromArray($data, $this->entityName);
        }

        return null;
    }

    public function findAll()
    {
        $entities = [];
        foreach($this->redisClient->lrange($this->generateKeyIds(), 0, -1) as $id){
            $entities[] = $this->find($id);
        }

        return $entities;
    }

    public function findBy(array $criteria, $sort = null, $limit = null, $offset = 0)
    {
        $indexesKeys = [];
        foreach($criteria as $indexName=>$indexValue) {
            if(!in_array($indexName, $this->entityIndexes)) {
                //todo internal exception
                throw new \Exception("'$indexName' index must be handled by '".$this->entityName."' class");
            }
            $indexesKeys[] = $this->generateKeyIndexByDirectValue($indexName, $indexValue);
        }
        $entities = [];
        $ids = $this->redisClient->sinter($indexesKeys);

        if($sort !== null) {
            if(strtoupper($sort)=='ASC') {
                asort($ids);
            } else {
                arsort($ids);
            }
        }

        if($limit != null) {
            $ids = array_slice($ids, $offset, $limit);
        }

        foreach($ids as $id) {
            $entities[] = $this->find($id);
        }

        return $entities;
    }

    /*
     * todo
     */
    public function count(array $criteria)
    {
        return 1000;
    }

    /**
     * @param StorageEntity $entity
     * @return StorageEntity
     */
    public function create(StorageEntity $entity)
    {
        //todo
        /*
        if($incrementId) {
        }
        */
        $entity->setId($this->findNextFreeId());
        $this->redisClient->multi();
        //list of all ids
        $this->redisClient->rpush($this->generateKeyIds(), [$entity->getId()]);
        //set entity data
        $this->redisClient->hmset(
            $this->generateKeyData($entity->getId()),
            $this->entityManager->getSerializer()->toArray($entity)
        );
        $this->createIndexes($entity);
        $this->redisClient->exec();

        return $entity;
    }

    public function update(StorageEntity $entity)
    {
        //todo check if exist before
        $this->redisClient->multi();
        //update data
        $this->redisClient->hmset(
            $this->generateKeyData($entity->getId()),
            $this->entityManager->getSerializer()->toArray($entity)
        );
        $this->removeIndexes($entity);
        $this->createIndexes($entity);
        $this->redisClient->exec();
    }

    /**
     * @param StorageEntity $entity
     */
    private function removeIndexes(StorageEntity $entity) {
        $this->redisClient->multi();
        foreach($this->entityIndexes as $index) {
            $this->redisClient->srem($this->generateKeyIndex($entity, $index), $entity->getId());
        }
        $this->redisClient->exec();
    }

    /**
     *
     * @param StorageEntity $entity
     */
    private function createIndexes(StorageEntity $entity) {
        $this->redisClient->multi();
        foreach($this->entityIndexes as $index) {
            $this->redisClient->sadd($this->generateKeyIndex($entity, $index), [$entity->getId()]);
        }
        $this->redisClient->exec();
    }

    /**
     * @param int $id
     * @return bool
     * @throws \Exception
     *
     */
    public function remove($id)
    {
        if(!$this->redisClient->exists($this->generateKeyData($id)))
        {
            return false;
        }
        $entity = $this->find($id);
        $this->redisClient->multi();

        //remove id in Ids list
        $this->redisClient->lrem($this->generateKeyIds(), 0, $id);
        //remove entity data
        $this->redisClient->del($this->generateKeyData($id));
        //remove id in indexes
        foreach($this->entityIndexes as $index) {
            $keyIndex = $this->generateKeyIndex($entity, $index);
            $this->redisClient->srem($keyIndex, $id);
            //remove index key if useless
            if($this->redisClient->scard($keyIndex) === 0) {
                $this->redisClient->del($keyIndex);
            }
        }
        unset($entity);
        $this->redisClient->exec();

        return true;
    }

    /**
     *
     * increment ID until new ID not used found
     *
     * @return int
     */
    public function findNextFreeId()
    {
        $this->getClassName();
        $continue = true;
        while($continue) {
            $id = $this
                ->redisClient
                ->incr($this->generateKeyPrimaryIncr());

            if(!$this->redisClient->exists($this->generateKeyData($id))) {
                $continue = false;
            }
        }

        return $id;
    }

    /**
     * @param string $name
     * @return string
     */
    public function generateKeyPrimaryIncr() {
        return str_replace(
            array('%prefix%', '%entityName%'),
            array($this->generatePrefix(), $this->entityTableName),
            self::KEY_PATTERN_PRIMARY_INCR
        );
    }

    /**
     * @param $id
     * @return string
     */
    public function generateKeyData($id) {
        return str_replace(
            array('%prefix%', '%entityName%', '%entityId%'),
            array($this->generatePrefix(), $this->entityTableName, $id),
            self::KEY_PATTERN_DATA
        );
    }

    /**
     * @return string
     */
    public function generateKeyIds() {
        return str_replace(
            array('%prefix%', '%entityName%'),
            array($this->generatePrefix(), $this->entityTableName),
            self::KEY_PATTERN_IDS
        );
    }

    /**
     * todo > change exception
     *
     * @param StorageEntity $entity
     * @param string $indexName
     * @return string
     * @throws \Exception
     */
    public function generateKeyIndex(StorageEntity $entity, $indexName) {
        $getterMethod = $this->getGetterMethod($indexName);
        if(!method_exists($entity, $getterMethod)) {
            throw new \Exception('Impossible to find ' . $getterMethod . ' in entity of class: ' . get_class($entity));
        }

        return str_replace(
            array('%prefix%', '%entityName%', '%indexName%', '%indexValue%'),
            array($this->generatePrefix(), $this->entityTableName, $indexName, $entity->{$getterMethod}()),
            self::KEY_PATTERN_INDEX
        );
    }

    /**
     * @param $name
     * @param $indexName
     * @param $indexValue
     * @return string
     */
    public function generateKeyIndexByDirectValue($indexName, $indexValue) {
        return str_replace(
            array('%prefix%', '%entityName%', '%indexName%', '%indexValue%'),
            array($this->generatePrefix(), $this->entityTableName, $indexName, $indexValue),
            self::KEY_PATTERN_INDEX
        );
    }

    /**
     * @return string
     */
    public function generatePrefix(){
        return self::GLOBAL_PREFIX;
    }

    /**
     * build 'getTotoMethod' string from 'totoMethod' or 'toto_method'
     *
     * @param string $name
     * @return string getMethodName
     */
    public function getGetterMethod($name) {
        return 'get' . str_replace('_','', ucwords($name, '_'));
    }

    /**
     * @return string
     */
    public function getEntityTableName()
    {
        return $this->entityTableName;
    }

    /**
     * @return array
     */
    public function getEntityIndexes()
    {
        return $this->entityIndexes;
    }
}