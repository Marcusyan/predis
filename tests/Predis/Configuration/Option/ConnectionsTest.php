<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use PredisTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Configuration\OptionsInterface;

/**
 *
 */
class ConnectionsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Connections();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Predis\Connection\Factory', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsNamedArrayWithSchemeToConnectionClassMappings(): void
    {
        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $class = get_class($this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock());
        $value = array('tcp' => $class, 'redis' => $class);

        $default = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $default
            ->expects($this->exactly(2))
            ->method('define')
            ->with($this->matchesRegularExpression('/^tcp|redis$/'), $class);

        /** @var OptionInterface */
        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
            ->onlyMethods(array('getDefault'))
            ->getMock();
        $option
            ->expects($this->once())
            ->method('getDefault')
            ->with($options)
            ->will($this->returnValue($default));

        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $factory = $option->filter($options, $value));
        $this->assertSame($default, $factory);
    }

    /**
     * @group disconnected
     */
    public function testUsesParametersOptionToSetDefaultParameters(): void
    {
        $parameters = array('database' => 5, 'password' => 'mypassword');

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('defined')
            ->with('parameters')
            ->will($this->returnValue(true));
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('parameters')
            ->will($this->returnValue($parameters));

        $option = new Connections();
        $factory = $option->getDefault($options);

        $this->assertSame($parameters, $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsConnectionFactoryInstance(): void
    {
        /** @var OptionInterface */
        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
            ->onlyMethods(array('getDefault'))
            ->getMock();
        $option
            ->expects($this->never())
            ->method('getDefault');

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $this->assertSame($factory, $option->filter($options, $factory));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningConnectionFactoryInstance(): void
    {
        $option = new Connections();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue(
                $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock()
            ));

        $this->assertSame($factory, $option->filter($options, $callable));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidArguments(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Connections expects a valid connection factory');

        $option = new Connections();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, new \stdClass());
    }
}
