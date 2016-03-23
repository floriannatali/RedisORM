<?php

namespace Sportnco\RedisORM\Annotation;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 *
 * @package Sportnco\RedisORM\Annotation
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class Entity implements RedisORM
{
    /**
     * @Required
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $autoIncrement=false;

}