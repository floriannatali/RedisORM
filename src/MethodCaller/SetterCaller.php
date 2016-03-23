<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 21/03/16
 * Time: 16:54
 */

namespace Sportnco\RedisORM\MethodCaller;


class SetterCaller extends AbstractCaller
{
    public function buildMethodName($property)
    {
        return  [
            'set' . str_replace('_','', ucwords($property, '_'))
        ];
    }
}