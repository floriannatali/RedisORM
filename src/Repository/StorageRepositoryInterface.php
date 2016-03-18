<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 15:09
 */

namespace Sportnco\RedisORM\Repository;


use Sportnco\RedisORM\Entity\StorageEntity;
use Sportnco\RedisORM\Exception\EntityAlreadyExistException;
use Sportnco\RedisORM\Exception\EntityNotFound;
use Sportnco\RedisORM\Exception\EntityNotFoundException;

/**
 * Repository for the storage entities
 *
 * @author Florian NATALI <florian.natali@gmail.com>
 */
interface StorageRepositoryInterface
{
    /**
     * Finds an object by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     *
     * @return StorageEntity
     */
    public function find($id);

    /**
     * Finds all objects in the repository.
     * Warning, on a massive storage database, can return lot of results!
     *
     * @return StorageEntity[]
     */
    public function findAll();

    /**
     * Finds objects by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param array      $criteria
     * @param string     $sort : (default) 'ASC' | 'DESC'
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return StorageEntity[]
     *
     * @throws \UnexpectedValueException
     */
    public function findBy(array $criteria, $sort = null, $limit = null, $offset = null);

    /**
     * Return number of objects by the set of criteria
     *
     * can be used for pagination, in addition with "findBy" method, setting the limit / offset parameters and same criteria
     *
     * @param array $criteria
     * @return integer
     */
    public function count(array $criteria);

    /**
     * store a new entity and return it (with his new id if not set)
     *
     * @throws EntityAlreadyExistException
     * @param StorageEntity $storageEntity
     * @return StorageEntity
     */
    public function create(StorageEntity $storageEntity);

    /**
     * update an entity
     * throw exception if trying to update a not existing entity
     *
     * @throws EntityNotFoundException
     *
     * @param StorageEntity $storageEntity
     * @return StorageEntity
     */
    public function update(StorageEntity $storageEntity);

    /**
     * Returns the entity stored
     *
     * @param integer $id
     * @return boolean true if entity found and removed, false if entity not found
     */
    public function remove($id);

    /**
     * Returns the entity name of the object managed by the repository.
     *
     * @return string
     */
    public function getEntityName();

    /**
     * @return string
     */
    public function getEntityTableName();

    /**
     * @return array
     */
    public function getEntityIndexes();
}