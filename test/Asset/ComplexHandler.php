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
use RuntimeException;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;
use pine3ree\test\Http\Server\Asset\Foo;

/**
 * ComplexHandler has a different constructor
 */
class ComplexHandler extends InvokableRequestHandler implements RequestHandlerInterface
{
    private Foo $foo;
    public function __construct(ParamsResolverInterface $paramsResolver, Foo $foo)
    {
        parent::__construct($paramsResolver);
        $this->foo = $foo;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse();
    }
}
