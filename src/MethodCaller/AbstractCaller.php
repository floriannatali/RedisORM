<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 21/03/16
 * Time: 16:58
 */

namespace Sportnco\RedisORM\MethodCaller;
use Sportnco\RedisORM\Exception\InvalidArgumentException;


abstract class AbstractCaller
{
    public function call() {
        $nbArgs =  func_num_args();
        if($nbArgs < 2) {
            throw new InvalidArgumentException('2 arguments minimum are required. The first must an Object instance, the second a property name. Other args will be passed to the method');
        }

        $args = func_get_args();
        $objectInstance = $args[0];
        if(!is_object($objectInstance)) {
            throw new InvalidArgumentException('The first argument must be an object instance');
        }

        $objectProperty = $args[1];

        $methodFound = null;
        foreach($this->buildMethodName($objectProperty) as $methodName) {
            if(method_exists($objectInstance, $methodName)) {
                $methodFound = $methodName;
                break;
            }
        }

        if(null === $methodFound) {
            throw new InvalidArgumentException("no callable method found with property '$objectProperty' on object: ". get_class($objectInstance));
        }

        $otherParams = [];
        for($i=2;$i<$nbArgs;$i++) {
            $otherParams[] = $args[$i];
        }

        return call_user_func_array(array($objectInstance, $methodFound), $otherParams);
    }

    /**
     * Possible method names, ordered by priority to call
     *
     * @param $property
     * @return array
     */
    abstract function buildMethodName($property);
}