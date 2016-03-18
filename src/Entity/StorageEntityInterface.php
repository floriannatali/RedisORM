<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 15:16
 */

namespace Sportnco\RedisORM\Entity;


interface StorageEntityInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @return string
     */
    static function getTableName();

    /**
     * @return string[]
     */
    static function getIndexes();
}