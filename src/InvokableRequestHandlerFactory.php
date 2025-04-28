<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Http\Server;

use Psr\Container\ContainerInterface;
use pine3ree\Container\ParamsResolver;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * A generic factory for invokable-handlers which use method-injection for
 * dependencies using reflection
 */
class InvokableRequestHandlerFactory
{
    public function __invoke(ContainerInterface $container, string $handlerFQCN): InvokableRequestHandler
    {
        return new $handlerFQCN($container->get(ParamsResolver::class));
    }
}
