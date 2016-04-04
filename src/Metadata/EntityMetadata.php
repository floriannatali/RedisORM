<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 18/03/16
 * Time: 17:06
 */

namespace Sportnco\RedisORM\Metadata;


use Sportnco\RedisORM\Exception\InvalidMetadataAnnotationException;

class EntityMetadata
{
    CONST ID_TYPE_SINGLE    =   'ID_TYPE_SINGLE';
    CONST ID_TYPE_MULTIPLE  =   'ID_TYPE_MULTIPLE';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string []
     */
    protected $indexesProperties;

    /**
     * @var boolean
     */
    protected $autoIncrement=false;

    /**
     * @var mixed[]
     */
    protected $idsProperties;

    /**
     * @var string[]
     */
    protected $orderByProperties;

    /**
     * @var string
     */
    protected $idType;

    /**
     * @var string[]
     */
    protected $datesProperties;

    public function __construct()
    {
        $this->indexesProperties = [];
        $this->idsProperties = [];
        $this->orderByProperties = [];
        $this->datesProperties = [];
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return \string[]
     */
    public function getIndexesProperties()
    {
        return $this->indexesProperties;
    }

    /**
     * @return \mixed[]
     */
    public function getIdsProperties()
    {
        return $this->idsProperties;
    }

    /**
     * @return \string[]
     */
    public function getOrderByProperties()
    {
        return $this->orderByProperties;
    }

    /**
     * @return \string[]
     */
    public function getDatesProperties()
    {
        return $this->datesProperties;
    }

    /**
     * @param $entityClass
     * @return EntityMetadata $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @param $entityName
     * @return EntityMetadata $this
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * @param $indexProperty
     * @return EntityMetadata $this
     */
    public function addIndexProperty($indexProperty)
    {
        $this->indexesProperties[] = $indexProperty;

        return $this;
    }

    /**
     * @param $idProperty
     * @return EntityMetadata $this
     */
    public function addIdProperty($idProperty)
    {
        $this->idsProperties[] = $idProperty;

        return $this;
    }

    /**
     * @param $orderByProperty
     * @return EntityMetadata $this
     */
    public function addOrderByProperty($orderByProperty)
    {
        $this->orderByProperties[] = $orderByProperty;

        return $this;
    }

    /**
     * @param $dateProperty
     * @return EntityMetadata $this
     */
    public function addDateProperty($dateProperty)
    {
        $this->datesProperties[] = $dateProperty;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param boolean $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return string
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * @param string $idType
     */
    public function setIdType($idType)
    {
        $this->idType = $idType;
    }

    /**
     * @return mixed
     * @throws InvalidMetadataAnnotationException
     */
    public function getIdProperty() {
        if($this->idType == self::ID_TYPE_SINGLE && count($this->idsProperties) == 1) {
            return $this->idsProperties[0];
        }

        throw new InvalidMetadataAnnotationException($this->entityClass. ': Impossible to find unique ID property. Verify your "ID" annotation configuration');
    }
}