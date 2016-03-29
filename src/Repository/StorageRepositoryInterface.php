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
use Sportnco\RedisORM\Exception\InvalidArgumentException;

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
     * @param array|mixed $id The identifiern can be an array of multiple ID properties key=>value,  or just a single ID value
     *
     * @return Object|null
     */
    public function find($id);

    /**
     * Finds all objects
     * Warning, on a massive storage database can return lot of results!
     * You should use count() to check number of results and paginate with $offset and $limit parameters
     *
     *
     * @param $sort
     * @param $limit
     * @param $offset
     * @param $orderBy
     * @return mixed
     */
    public function findAll($sort, $limit, $offset, $orderBy);

    /**
     * Finds objects by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an InvalidArgumentException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param array      $criteria
     *
     * @return Object[]
     *
     * @throws InvalidArgumentException
     */
    public function findBy(array $criteria);

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
     * @param Object $entity
     * @return Object
     */
    public function insert($entity);

    /**
     * update an entity
     * throw exception if trying to update a not existing entity
     *
     * @throws EntityNotFoundException
     *
     * @param Object $entity
     * @return Object
     */
    public function update($entity);

    /**
     * Returns the entity stored
     *
     * @param integer $id
     * @return boolean true if entity found and removed, false if entity not found
     */
    public function remove($id);
}