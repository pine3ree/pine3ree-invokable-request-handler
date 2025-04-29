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
use pine3ree\test\Http\Server\Asset\IncompleteHandler;
use pine3ree\test\Http\Server\Asset\InvalidHandler;

use function array_merge;

class InvokableRequestHandlerTest extends TestCase
{
    /**
     * set up test environmemt
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testThatMethodInjectionWorksIfDependenciesAreFoundTheContainer()
    {
        $foo = new Foo('foo');
        $bar = new Bar();

        $container = $this->getContainerMock([
            Foo::class => $foo,
            Bar::class => $bar,
        ]);

        $request = $this->getServerRequestMock();

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

    public function testThatMethodInjectionCanOverrideContainerDependencies()
    {
        $foo = new Foo('foo');
        $bar = new Bar();

        $container = $this->getContainerMock([
            Foo::class => $foo,
            Bar::class => $bar,
        ]);

        self::assertSame($foo, $container->get(Foo::class));
        self::assertSame($bar, $container->get(Bar::class));

        $attributes = [
            Bar::class => $requestBar = new Bar(),
        ];

        $request = $this->getServerRequestMock($attributes);

        self::assertSame($requestBar, $request->getAttribute(Bar::class));
        self::assertEquals([Bar::class => $requestBar], $request->getAttributes());

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($container->get(Foo::class), $handler->getCurrentFoo());
        self::assertNotSame($container->get(Bar::class), $handler->getCurrentBar());
        self::assertSame($request->getAttribute(Bar::class), $handler->getCurrentBar());
    }

    public function testThatRequestAttributesAreInjectedIfSameNameArgumentIsFound()
    {
        $container = $this->getContainerMock();

        $attributes = [
            'year' => 1492,
        ];

        $request = $this->getServerRequestMock($attributes);

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($request->getAttribute('year'), $handler->getCurrentYear());
    }

    public function testThatDefaultArgumentValuesAreUsedIfNotInContainer()
    {
        $container = $this->getContainerMock(['year' => null]);

        $request = $this->getServerRequestMock();

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame(Handler::YEAR, $handler->getCurrentYear());
    }

    public function testThatContainerValuesAreUsedIfNotInRequestAttributes()
    {
        $container = $this->getContainerMock(['year' => 1492]);

        $request = $this->getServerRequestMock();

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($container->get('year'), $handler->getCurrentYear());
    }

    public function testThatInvalidInvokeReturnValueRaisesException()
    {
        $container = $this->getContainerMock();

        $request = $this->getServerRequestMock();

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, InvalidHandler::class);

        $this->expectException(RuntimeException::class);
        $handler->handle($request);
    }

    public function testThatMissingInvokeDefinitionRaisesException()
    {
        $container = $this->getContainerMock();

        $request = $this->getServerRequestMock();

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, IncompleteHandler::class);

        $this->expectException(RuntimeException::class);
        $handler->handle($request);
    }

    public function testThatUnresolvableDependenciesRaiseException()
    {
        $container = $this->getContainerMock([Foo::class => null]);

        $request = $this->getServerRequestMock();

        $factory = new InvokableRequestHandlerFactory();

        $handler = $factory($container, Handler::class);

        $this->expectException(RuntimeException::class);
        $handler->handle($request);
    }

    private function getContainerMock(?array $getMergeMap = null, ?array $hasMap = null): ContainerInterface
    {
        $containerKeys = [
            ParamsResolverInterface::class,
            Foo::class,
            Bar::class,
            'year',
        ];

        $defaulGetMap = [
            Foo::class => new Foo('foo'),
        ];

        $getMap = $getMergeMap ? array_merge($defaulGetMap, $getMergeMap) : $defaulGetMap;
        $getReturnMap = [];
        foreach ($getMap as $name => $value) {
            $getReturnMap[] = [$name, $value];
        }

        $hasMap = [];
        foreach ($containerKeys as $key) {
            $hasMap[$key] = isset($getMap[$key]);
        }
        $hasReturnMap = [];
        foreach ($hasMap as $name => $value) {
            $hasReturnMap[] = [$name, $value];
        }

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap($hasReturnMap);
        if (!empty($getReturnMap)) {
            $container->method('get')->willReturnMap($getReturnMap);
        }

        return $container;
    }
    private function getServerRequestMock(array $attributes = []): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttributes')->willReturn($attributes);

        if (empty($attributes)) {
            $request->method('getAttribute')->willReturn(null);
            return $request;
        }

        $returnMap = [];
        foreach ($attributes as $name => $value) {
            $returnMap[] = [$name, null, $value]; // NULL is the default value
        }

        $request->method('getAttribute')->willReturnMap($returnMap);

        return $request;
    }
}
