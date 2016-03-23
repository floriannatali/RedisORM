<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 18/03/16
 * Time: 16:49
 */

namespace Sportnco\RedisORM\Annotation;

/**
 *
 * @package Sportnco\RedisORM\Annotation
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Date implements RedisORM
{
    /**
     * @var string
     */
    public $format='';
}