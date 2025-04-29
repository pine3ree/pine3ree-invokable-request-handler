<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Http\Server;

use Psr\Container\ContainerInterface;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * A generic factory for invokable-handlers which use method-injection for
 * dependencies using reflection
 *
 * The invokable-handler class constructor must only accept a single argument of
 * type ParamsResolverInterface
 */
class InvokableRequestHandlerFactory
{
    public function __invoke(ContainerInterface $container, string $handlerFQCN): InvokableRequestHandler
    {
        // Fetch the params-resolver service from container
        $paramsResolver = $container->has(ParamsResolverInterface::class)
            ? $container->get(ParamsResolverInterface::class)
            : null;

        // If not found create a new one
        if ($paramsResolver === null || ! $paramsResolver instanceof ParamsResolverInterface) {
            $paramsResolver = new ParamsResolver($container);
        }

        return new $handlerFQCN($paramsResolver);
    }
}
