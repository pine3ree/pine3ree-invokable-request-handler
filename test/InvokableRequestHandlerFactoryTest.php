<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Http\Server;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use RuntimeException;
use SplObjectStorage;
use stdClass;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;
use pine3ree\Http\Server\InvokableRequestHandlerFactory;
use pine3ree\test\Http\Server\Asset\Handler;
use pine3ree\test\Http\Server\Asset\ComplexHandler;

class InvokableRequestHandlerFactoryTest extends TestCase
{
    /**
     * set up test environmemt
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testThatNonexistentHandlerClassesRaiseException()
    {
        $container = $this->getContainerMock();

        $factory = new InvokableRequestHandlerFactory();

        $this->expectException(RuntimeException::class);
        $factory($container, NonexistentHandler::class);
    }

    public function testThatInvalidHandlerClassesRaiseException()
    {
        $container = $this->getContainerMock();

        $factory = new InvokableRequestHandlerFactory();

        $this->expectException(RuntimeException::class);
        $factory($container, stdClass::class);
    }

    public function testThatFactoryUsesParamsResolverInContainerIfFound()
    {
        $paramsResolver = $this->createMock(ParamsResolverInterface::class);
        $container = $this->getContainerMock($paramsResolver);

        $container->method('has')->with(ParamsResolverInterface::class)->willReturn(true);
        $container->method('get')->with(ParamsResolverInterface::class)->willReturn($paramsResolver);

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        self::assertInstanceOf(InvokableRequestHandler::class, $handler);
        self::assertInstanceOf(Handler::class, $handler);
        self::assertInstanceOf(ParamsResolverInterface::class, $handler->getParamsResolver());
        self::assertSame($paramsResolver, $handler->getParamsResolver());
    }

    public function testThatFactoryCreatesNewParamsResolverIfNotFoundInContainer()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(ParamsResolverInterface::class)->willReturn(false);

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        self::assertInstanceOf(InvokableRequestHandler::class, $handler);
        self::assertInstanceOf(Handler::class, $handler);
        self::assertInstanceOf(ParamsResolverInterface::class, $handler->getParamsResolver());
        self::assertInstanceOf(ParamsResolver::class, $handler->getParamsResolver());
    }

    public function testThatFactoryUsesCachedParamsResolverForSameContainer()
    {
        $container = $this->getContainerMock();

        $factory = new InvokableRequestHandlerFactory();

        $handler1 = $factory($container, Handler::class);

        $cacheProp = new ReflectionProperty($factory, 'cache');
        $cacheProp->setAccessible(true);
        $cache = $cacheProp->getValue($factory);

        self::assertInstanceOf(SplObjectStorage::class, $cache);

        $paramsResolver1 = $cache->contains($container) ? $cache->offsetGet($container) : null;

        self::assertInstanceOf(ParamsResolverInterface::class, $paramsResolver1);

        $handler2 = $factory($container, Handler::class);
        $paramsResolver2 = $cache->contains($container) ? $cache->offsetGet($container) : null;

        self::assertInstanceOf(ParamsResolverInterface::class, $paramsResolver1);

        self::assertSame($paramsResolver1, $paramsResolver2);
    }

    public function testThatTheParamsResolverInstanceIsTheOneInjectedByFactory()
    {
        $container = $this->getContainerMock();

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        $cacheProp = new ReflectionProperty($factory, 'cache');
        $cacheProp->setAccessible(true);
        $cache = $cacheProp->getValue($factory);

        $paramsResolver = $cache->contains($container) ? $cache->offsetGet($container) : null;

        self::assertInstanceOf(ParamsResolverInterface::class, $paramsResolver);
        self::assertInstanceOf(Handler::class, $handler); /** @var Handler $handler */
        self::assertSame($paramsResolver, $handler->getParamsResolver());
    }

    public function testThatHandlerClassesWithComplexConstructorsRaiseException()
    {
        $container = $this->getContainerMock();

        $factory = new InvokableRequestHandlerFactory();

        $this->expectException(RuntimeException::class);
        $complexHandler = $factory($container, ComplexHandler::class);
    }

    private function getContainerMock(?ParamsResolverInterface $paramsResolver = null): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('has')->with(ParamsResolverInterface::class)->willReturn(isset($paramsResolver));
        $container->method('get')->with(ParamsResolverInterface::class)->willReturn($paramsResolver);

        return $container;
    }
}
