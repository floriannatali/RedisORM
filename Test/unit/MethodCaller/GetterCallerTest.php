<?php

namespace Sportnco\RedisORM\MethodCaller;


class GetterCallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GetterCaller
     */
    protected $instance;

    protected function setUp()
    {
        $this->instance = new GetterCaller();
    }

    public function dpTestBuildMethodName(){
        return [
            ['property'      , ['getProperty', 'isProperty', 'hasProperty']],
            ['propertyName'  , ['getPropertyName', 'isPropertyName', 'hasPropertyName']],
            ['property_name' , ['getPropertyName', 'isPropertyName', 'hasPropertyName']],
        ];
    }

    /**
     * @param $propertyName
     * @param $expectedMethodNames
     *
     * @dataProvider dpTestBuildMethodName
     */
    public function testBuildMethodName($propertyName, $expectedMethodNames)
    {
        $this->assertEquals($expectedMethodNames, $this->instance->buildMethodName($propertyName));
    }
}
