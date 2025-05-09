<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\test\Http\Server;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use RuntimeException;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;
use pine3ree\Http\Server\InvokableRequestHandlerFactory;
use pine3ree\test\Http\Server\Asset\AttributesHandler;
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
        $handler = $this->createHandler(Handler::class, $container);

        self::assertInstanceOf(InvokableRequestHandler::class, $handler);
        self::assertInstanceOf(Handler::class, $handler);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($foo, $handler->getFoo());
        self::assertSame($bar, $handler->getBar());
        self::assertSame($container->get(Foo::class), $handler->getFoo());
        self::assertSame($container->get(Bar::class), $handler->getBar());
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

        $handler = $this->createHandler(Handler::class, $container);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($container->get(Foo::class), $handler->getFoo());
        self::assertNotSame($container->get(Bar::class), $handler->getBar());
        self::assertSame($request->getAttribute(Bar::class), $handler->getBar());
    }

    public function testThatRequestAttributesAreInjectedIfSameNameArgumentIsFound()
    {
        $attributes = [
            'year' => 1492,
        ];

        $request = $this->getServerRequestMock($attributes);
        $handler = $this->createHandler(Handler::class);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($request->getAttribute('year'), $handler->getYear());
    }

    public function testThatDefaultArgumentValuesAreUsedIfNotInContainer()
    {
        $container = $this->getContainerMock(['year' => null]);

        $request = $this->getServerRequestMock();
        $handler = $this->createHandler(Handler::class, $container);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame(Handler::YEAR, $handler->getYear());
    }

    public function testThatContainerValuesAreUsedIfNotInRequestAttributes()
    {
        $container = $this->getContainerMock(['year' => 1492]);

        $request = $this->getServerRequestMock();
        $handler = $this->createHandler(Handler::class, $container);

        $handler->handle($request); // Triggers __invoke() via invokeHandler()

        self::assertSame($container->get('year'), $handler->getYear());
    }

    public function testThatInvalidInvokeReturnValueRaisesException()
    {
        $request = $this->getServerRequestMock();
        $handler = $this->createHandler(InvalidHandler::class);

        $this->expectException(RuntimeException::class);
        $handler->handle($request);
    }

    public function testThatMissingInvokeDefinitionRaisesException()
    {
        $request = $this->getServerRequestMock();
        $handler = $this->createHandler(IncompleteHandler::class);

        $this->expectException(RuntimeException::class);
        $handler->handle($request);
    }

    public function testThatUnresolvableDependenciesRaiseException()
    {
        $container = $this->getContainerMock([Foo::class => null]);

        $request = $this->getServerRequestMock();
        $handler = $this->createHandler(Handler::class, $container);

        $this->expectException(RuntimeException::class);
        $handler->handle($request);
    }

    /**
     * @dataProvider provideTypeCastingTestingValues
     */
    public function testTypeCasting(string $attributeName, $requestValue, $expectedValue)
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $request = $this->getServerRequestMock([
            $attributeName => $requestValue,
        ]);

        /** @var AttributesHandler $handler */
        $handler = $this->createHandler(AttributesHandler::class, $container);
        $handler->handle($request);

        self::assertEquals($expectedValue, $handler->getArg($attributeName));
    }

    public function provideTypeCastingTestingValues(): array
    {
        return [
            // ?int $customer_id = null
            ['customer_id', null, null],
            ['customer_id', 42, 42],
            ['customer_id', '42', 42],
            ['customer_id', 42.0, 42],
            ['customer_id', true, 1],
            ['customer_id', false, 0],
            ['customer_id', '', 0],
            // int $product_id = 0
            ['product_id', null, 0],
            // ?string $title = null
            ['title', null, null],
            ['title', '', ''],
            ['title', 'abc','abc'],
            ['title', 123, '123'],
            ['title', 12.0, '12'],
            ['title', 12.3, '12.3'],
            ['title', true, '1'],
            ['title', false, ''],
            // string $slug = ''
            ['slug', null, ''],
            // ?float $price = null
            ['price', null, null],
            ['price', 0, 0.0],
            ['price', 123, 123.0],
            ['price', '123', 123],
            ['price', '12.3', 12.3],
            ['price', true, 1.0],
            ['price', false, 0.0],
            ['price', '', 0.0],
            // bool $flag = null
            ['vat', null, 0.0],
            // bool $flag = null
            ['flag', null, null],
            ['flag', true, true],
            ['flag', false, false],
            ['flag', 1, true],
            ['flag', 0, false],
            ['flag', '1', true],
            ['flag', '0', false],
            ['flag', 'true', true],
            ['flag', 'false', false],
            ['flag', 'yes', true],
            ['flag', 'no', false],
            ['flag', '', false],
            ['flag', 'a', null], // NULL on failure
            ['flag', 123, null], // NULL on failure
            ['flag', -123, null], // NULL on failure
            // bool $truth = true
            ['truth', null, true],
            ['truth', 123, true], // NULL on failure => use default TRUE
            ['truth', -123, true], // NULL on failure => use default TRUE
            // bool $lie = false
            ['lie', null, false],
            ['lie', 123, false], // NULL on failure => use default FALSE
            ['lie', -123, false], // NULL on failure => use default FALSE
            // ?array $array1 = null,
            ['array1', null, null],
            ['array1', [], []],
            ['array1', [1, 2, 3], [1, 2, 3]],
            // array $array2 = [,
            ['array2', null, []],
            // $uanswer = 42
            ['uanswer', null, 42],
            ['uanswer', '', ''],
            ['uanswer', 'abc', 'abc'],
            ['uanswer', [42], [42]],
            ['uanswer', 42.0, 42.0],
            ['uanswer', false,false],
            ['uanswer', null, 42],
            // $unullable = null
            ['unullable', null, null],
            ['unullable', 'abc', 'abc'],
        ];
    }


    private function createHandler(string $handlerClass, ?ContainerInterface $container = null): RequestHandlerInterface
    {
        $factory = new InvokableRequestHandlerFactory();
        $container ??= $this->getContainerMock();

        return $factory($container, $handlerClass);
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
