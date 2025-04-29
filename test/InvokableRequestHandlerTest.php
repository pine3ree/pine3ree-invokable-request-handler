<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Http\Server;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use RuntimeException;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;
use pine3ree\Http\Server\InvokableRequestHandlerFactory;
use pine3ree\test\Http\Server\Asset\Bar;
use pine3ree\test\Http\Server\Asset\Foo;
use pine3ree\test\Http\Server\Asset\Handler;

class InvokableRequestHandlerTest extends TestCase
{
    /**
     * set up test environmemt
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testThatTheParamsResolverInstanceIsTheOneInjectedByFactory()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(ParamsResolverInterface::class)->willReturn(false);

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        $cacheProp = new ReflectionProperty($factory, 'cache');
        $cacheProp->setAccessible(true);
        $cache = $cacheProp->getValue($factory);

        $paramsResolver = $cache->contains($container) ? $cache->offsetGet($container) : null;

        self::assertInstanceOf(ParamsResolverInterface::class, $paramsResolver);
        self::assertInstanceOf(Handler::class, $handler);
        self::assertSame($paramsResolver, $handler->getParamsResolver());
    }

    public function testThatMethodInjectionWorksIfDependenciesAreFoundTheContainer()
    {
        $foo = new Foo();
        $bar = new Bar();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            [ParamsResolverInterface::class, false],
            [Foo::class, true],
            [Bar::class, true],
        ]);
        $container->method('get')->willReturnMap([
            [Foo::class, $foo],
            [Bar::class, $bar],
        ]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttributes')->willReturn([]);

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        self::assertInstanceOf(InvokableRequestHandler::class, $handler);
        self::assertInstanceOf(Handler::class, $handler);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($foo, $handler->getCurrentFoo());
        self::assertSame($bar, $handler->getCurrentBar());
        self::assertSame($container->get(Foo::class), $handler->getCurrentFoo());
        self::assertSame($container->get(Bar::class), $handler->getCurrentBar());
    }
}
