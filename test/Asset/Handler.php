<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

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
    private ?ServerRequestInterface $currentRequest = null;
    private ?Foo $currentFoo = null;
    private ?Bar $currentBar = null;
    private ?int $currentExtra = null;

    public function __invoke(
        ServerRequestInterface $request,
        Foo $foo = null,
        Bar $bar = null,
        ?int $extra = null
    ): ResponseInterface {
        // Set current values for testing
        $this->currentRequest = $request;
        $this->currentFoo = $foo;
        $this->currentBar = $bar;
        $this->currentExtra = $extra;

        return new HtmlResponse('');
    }

    public function getParamsResolver(): ParamsResolverInterface
    {
        return $this->paramsResolver;
    }

    public function getCurrentRequest(): ?ServerRequestInterface
    {
        return $this->currentRequest;
    }

    public function getCurrentFoo(): ?Foo
    {
        return $this->currentFoo;
    }

    public function getCurrentBar(): ?Bar
    {
        return $this->currentBar;
    }

    public function getCurrentExtra(): ?int
    {
        return $this->currentExtra;
    }
}
