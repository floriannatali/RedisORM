<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 21/03/16
 * Time: 10:29
 */

namespace Sportnco\RedisORM\Metadata;


interface ValidatorInterface
{
    /**
     * @param EntityMetadata $entityMetadata
     * @return EntityMetadata
     */
    public function validate(EntityMetadata $entityMetadata);
}