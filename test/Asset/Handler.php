<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\test\Http\Server\Asset;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * Minimal Invokable Handler for unit tests
 */
class Handler extends InvokableRequestHandler implements RequestHandlerInterface
{
    private ?ServerRequestInterface $Request = null;
    private ?Foo $Foo = null;
    private ?Bar $Bar = null;
    private ?int $Year = null;

    public const YEAR = 1970;

    public function __invoke(
        ServerRequestInterface $request,
        Foo $foo,
        Bar $bar = null,
        ?int $year = self::YEAR
    ): ResponseInterface {
        // Set  values for testing
        $this->request = $request;
        $this->foo = $foo;
        $this->bar = $bar;
        $this->year = $year;

        return new HtmlResponse('');
    }

    public function getParamsResolver(): ParamsResolverInterface
    {
        return $this->paramsResolver;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function getFoo(): ?Foo
    {
        return $this->foo;
    }

    public function getBar(): ?Bar
    {
        return $this->bar;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }
}
