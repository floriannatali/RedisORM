<?php

namespace Sportnco\RedisORM\KeyBuilder;


use Sportnco\RedisORM\Metadata\EntityMetadata;
use Sportnco\RedisORM\MethodCaller\GetterCaller;

class KeyBuilder
{
    /**
     * @var GetterCaller
     */
    protected $getterCaller;

    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @param EntityMetadata $entityMetadata
     * @param GetterCaller $getterCaller
     * @param string $prefix
     */
    public function __construct(EntityMetadata $entityMetadata, GetterCaller $getterCaller, $prefix='')
    {
        $this->getterCaller = $getterCaller;
        $this->entityMetadata = $entityMetadata;
        $this->prefix = $prefix;
    }


    /**
     * @return EntityMetadata
     */
    public function getEntityMetadata()
    {
        return $this->entityMetadata;
    }

    /**
     * @param EntityMetadata $entityMetadata
     * @return KeyBuilder $this
     */
    public function setEntityMetadata(EntityMetadata $entityMetadata)
    {
        $this->entityMetadata = $entityMetadata;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     *  @return KeyBuilder $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }
}