<?php

/**
 * @package     package
 * @subpackage  package-subpackage
 * @author      pine3ree https://github.com/pine3ree
 */

namespace pine3ree\test\Http\Server\Asset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * Class Handler
 */
class Handler extends InvokableRequestHandler implements RequestHandlerInterface
{
    public function __invoke(
        ServerRequestInterface $request,
        Foo $foo,
        Bar $bar
    ): ResponseInterface {
        // no op
    }

    public function getParamsResolver(): ParamsResolverInterface
    {
        return $this->paramsResolver;
    }
}
