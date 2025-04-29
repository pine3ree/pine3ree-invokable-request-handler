<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Http\Server;

use Psr\Container\ContainerInterface;
use RuntimeException;
use SplObjectStorage;
use Throwable;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * A generic factory for invokable-handlers whose constructors only accepts a
 * single argument of type ParamsResolverInterface
 */
class InvokableRequestHandlerFactory
{
    private ?SplObjectStorage $cache = null;

    public function __invoke(ContainerInterface $container, string $handlerFQCN): InvokableRequestHandler
    {
        $cache = $this->cache ?? $this->cache = new SplObjectStorage();

        /** @var SplObjectStorage $cache Previous line ensures this */
        if ($cache->contains($container)) {
            $paramsResolver = $cache->offsetGet($container);
        } else {
            if ($container->has(ParamsResolverInterface::class)) {
                $paramsResolver = $container->get(ParamsResolverInterface::class);
                if ($paramsResolver instanceof ParamsResolverInterface !== true) {
                    $paramsResolver = new ParamsResolver($container);
                }
            } else {
                $paramsResolver = new ParamsResolver($container);
            }
            $cache->attach($container, $paramsResolver);
        }

        try {
            return new $handlerFQCN($paramsResolver);
        } catch (Throwable $ex) {
            throw new RuntimeException($ex->getMessage());
        }
    }
}
