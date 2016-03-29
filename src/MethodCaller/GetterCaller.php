<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 21/03/16
 * Time: 17:03
 */

namespace Sportnco\RedisORM\MethodCaller;

class GetterCaller extends AbstractCaller
{
    /**
     * {@inheritDoc}
     */
    public function buildMethodName($property)
    {

        $formatted = str_replace('_','', ucwords($property, '_'));
        return  [
            'get' . $formatted,
            'is' . $formatted,
            'has' . $formatted
        ];
    }
}