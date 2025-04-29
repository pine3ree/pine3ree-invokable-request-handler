<?php

/**
 * @package     pine3ree-abstract-factories
 * @subpackage  pine3ree-abstract-factories-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Http\Server;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use SplObjectStorage;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;
use pine3ree\Http\Server\InvokableRequestHandlerFactory;
use pine3ree\test\Http\Server\Asset\Handler;

class InvokableRequestHandlerFactoryTest extends TestCase
{
    /**
     * set up test environmemt
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testThatFactoryUsesParamsResolverInContainerIfFound()
    {
        $container = $this->createMock(ContainerInterface::class);
        $paramsResolver = $this->createMock(ParamsResolverInterface::class);

        $container->method('has')->with(ParamsResolverInterface::class)->willReturn(true);
        $container->method('get')->with(ParamsResolverInterface::class)->willReturn($paramsResolver);

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        self::assertInstanceOf(InvokableRequestHandler::class, $handler);
        self::assertInstanceOf(Handler::class, $handler);
        self::assertInstanceOf(ParamsResolverInterface::class, $handler->getParamsResolver());
        self::assertSame($paramsResolver, $handler->getParamsResolver());
    }

    public function testThatFactoryUsesCreatesNewParamsResolverIfNotFoundInContainer()
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

    public function testThatFactoryUsesCachesParamsResolverForSameContainer()
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('has')->with(ParamsResolverInterface::class)->willReturn(false);

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
}
