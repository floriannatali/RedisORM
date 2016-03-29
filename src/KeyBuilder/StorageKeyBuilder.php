<?php

namespace Sportnco\RedisORM\KeyBuilder;


use Sportnco\RedisORM\Exception\InvalidArgumentException;

class StorageKeyBuilder extends KeyBuilder
{
    CONST KEY_PATTERN_PRIMARY_INCR  = "%prefix%%entityName%:primary-incr";
    CONST KEY_PATTERN_DATA          = "%prefix%%entityName%:data:%entityId%";
    CONST KEY_PATTERN_INDEX         = "%prefix%%entityName%:index:%indexName%:%indexValue%";
    CONST KEY_PATTERN_ORDER_BY      = "%prefix%%entityName%:order-by:%orderByName%";
    CONST KEY_PATTERN_DATE_INDEX    = "%prefix%%entityName%:date-index:%indexName%";
    CONST KEY_PATTERN_IDS           = "%prefix%%entityName%:ids";
    CONST KEY_PATTERN_ORDERED_IDS  = "%prefix%%entityName%:ordered-ids";

    /**
     * @return string
     */
    public function generateKeyPrimaryIncr()
    {
        return str_replace(
            array('%prefix%', '%entityName%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName()),
            self::KEY_PATTERN_PRIMARY_INCR
        );
    }

    /**
     * @param $idString
     * @return string
     */
    public function generateKeyData($idString)
    {
        return str_replace(
            array('%prefix%', '%entityName%', '%entityId%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName(), $idString),
            self::KEY_PATTERN_DATA
        );
    }

    /**
     * @return string
     */
    public function generateKeyIds()
    {
        return str_replace(
            array('%prefix%', '%entityName%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName()),
            self::KEY_PATTERN_IDS
        );
    }

    /**
     * @param Object $entity
     * @param $indexName
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function generateKeyIndex($entity, $indexName)
    {
        if (!in_array($indexName, $this->entityMetadata->getIndexesProperties())) {
            throw new InvalidArgumentException("Index '$indexName' is not managed by entity " . $this->entityMetadata->getEntityClass());
        }

        $propertyValue = $this->getterCaller->call($entity, $indexName);

        return str_replace(
            array('%prefix%', '%entityName%', '%indexName%', '%indexValue%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName(), $indexName, $propertyValue),
            self::KEY_PATTERN_INDEX
        );
    }

    /**
     * @param $indexName
     * @param $indexValue
     * @return string
     * @throws InvalidArgumentException
     */
    public function generateKeyIndexByDirectValue($indexName, $indexValue)
    {
        if (!in_array($indexName, $this->entityMetadata->getIndexesProperties())) {
            throw new InvalidArgumentException("Index '$indexName' is not managed by entity " . $this->entityMetadata->getEntityClass());
        }

        return str_replace(
            array('%prefix%', '%entityName%', '%indexName%', '%indexValue%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName(), $indexName, $indexValue),
            self::KEY_PATTERN_INDEX
        );
    }

    /**
     * @param $property
     * @return string
     * @throws InvalidArgumentException
     */
    public function generateKeyOrderBy($property)
    {
        if (!in_array($property, $this->entityMetadata->getOrderByProperties())) {
            throw new InvalidArgumentException("Order by '$property' is not managed by entity " . $this->entityMetadata->getEntityClass());
        }

        return str_replace(
            array('%prefix%', '%entityName%', '%orderByName%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName(), $property),
            self::KEY_PATTERN_ORDER_BY
        );
    }

    /**
     * @param $property
     * @return string
     * @throws InvalidArgumentException
     */
    public function generateKeyDateIndex($property)
    {
        if (!in_array($property, $this->entityMetadata->getDatesProperties())) {
            throw new InvalidArgumentException("Date index '$property' is not managed by entity " . $this->entityMetadata->getEntityClass());
        }

        return str_replace(
            array('%prefix%', '%entityName%', '%indexName%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName(), $property),
            self::KEY_PATTERN_DATE_INDEX
        );
    }

    /**
     * @return string
     */
    public function generateKeyOrderedIds()
    {
        return str_replace(
            array('%prefix%', '%entityName%'),
            array($this->getPrefix(), $this->entityMetadata->getEntityName()),
            self::KEY_PATTERN_ORDERED_IDS
        );
    }
}