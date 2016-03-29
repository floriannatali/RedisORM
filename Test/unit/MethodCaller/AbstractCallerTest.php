<?php


namespace Sportnco\RedisORM\MethodCaller;


use Sportnco\RedisORM\Exception\InvalidArgumentException;

class AbstractCallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractCaller
     */
    protected $instance;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fakeObject;

    protected function setUp()
    {
        $this->instance = $this->getMockForAbstractClass(AbstractCaller::class);
        $this->fakeObject = $this
            ->getMockBuilder('FakeObject')
            ->setMethods(['fakeMethod'])
            ->getMock();
    }

    public function testCall()
    {
        $this->instance
            ->expects($this->once())
            ->method('buildMethodName')
            ->with($this->equalTo('fakeProperty'))
            ->will($this->returnValue(['fakeMethod']));

        $this->fakeObject
            ->expects($this->once())
            ->method('fakeMethod')
            ->with(
                $this->equalTo('fakeArg1'),
                $this->equalTo('fakeArg2'),
                $this->equalTo('fakeArg3')
                )
            ->will($this->returnValue('fakeReturn'));

        $this->assertEquals('fakeReturn', $this->instance->call(
            $this->fakeObject,
            'fakeProperty',
            'fakeArg1',
            'fakeArg2',
            'fakeArg3'
        ));
    }

    public function testCallINotEnoughParameters()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $this->instance->call('just One parameter');
    }

    public function testCallNotObjectAsFirstParameter()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $this->instance->call('not an object', 'property');
    }

    public function testCallMethodNotFoundOnObject()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $this->instance
            ->expects($this->once())
            ->method('buildMethodName')
            ->with($this->equalTo('fakeProperty'))
            ->will($this->returnValue(['notFoundMethod']));

        $this->instance->call($this->fakeObject, 'fakeProperty');
    }
}
