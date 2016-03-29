<?php

namespace Sportnco\RedisORM\MethodCaller;

class SetterCallerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var SetterCaller
     */
    protected $instance;

    protected function setUp()
    {
        $this->instance = new SetterCaller();
    }

    public function dpTestBuildMethodName(){
        return [
            ['property'      , ['setProperty']],
            ['propertyName'  , ['setPropertyName']],
            ['property_name' , ['setPropertyName']],
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
