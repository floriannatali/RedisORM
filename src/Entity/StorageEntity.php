<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 17/03/16
 * Time: 14:42
 */

namespace Sportnco\RedisORM\Entity;

use JMS\Serializer\Annotation as Serializer;

abstract class StorageEntity implements StorageEntityInterface
{
    /**
     * @var int
     *
     * @Serializer\Type(name="integer")
     *
     */
    protected $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}