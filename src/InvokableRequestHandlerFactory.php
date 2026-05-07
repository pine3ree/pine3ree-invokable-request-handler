<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\Http\Server;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplObjectStorage;
use Throwable;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\Exception\RuntimeException;
use pine3ree\Http\Server\InvokableRequestHandler;
use pine3ree\Http\Server\InvokableRequestHandlerTrait;

use function class_exists;
use function class_parents;
use function class_uses;
use function in_array;
use function is_subclass_of;
use function sprintf;

/**
 * A generic factory for invokable-handlers whose constructors only accepts a
 * single argument of type ParamsResolverInterface
 */
class InvokableRequestHandlerFactory
{
    /**
     * Params resolvers cached by container
     * @var SplObjectStorage<ContainerInterface, ParamsResolverInterface>|null
     */
    private ?SplObjectStorage $cache = null;

    /**
     * Creates an invokable-request-handler instance injected with a params-resolver
     * either from the container or a newly create instance.
     *
     * The provided handler class MUST implement a simple constructor accepting
     * the params-resolver as the only argument, otherwise a custom or an autowiring
     * factory is required.
     *
     * The provided handler class is validated to use the invokable-request-handler-trait.
     *
     * The handler instance is injected with a params-resolver either from the container
     * or a newly create instance.
     *
     * Params resolvers are cached per-container.
     *
     * @param ContainerInterface $container
     * @param string $handlerFQCN The handler fully qualified class name
     * @return RequestHandlerInterface
     * @throws RuntimeException
     */
    public function __invoke(ContainerInterface $container, string $handlerFQCN): RequestHandlerInterface
    {
        if (!class_exists($handlerFQCN)) {
            throw new RuntimeException(
                "Unable to load the requested class `{$handlerFQCN}`"
            );
        }

        if (!is_subclass_of($handlerFQCN, RequestHandlerInterface::class)
            || !$this->classUsesInvokableTrait($handlerFQCN)
        ) {
            throw new RuntimeException(sprintf(
                "`%s` must either be a subclass of `%s` or implement `%s` using the trait `%s`.",
                $handlerFQCN,
                InvokableRequestHandler::class,
                RequestHandlerInterface::class,
                InvokableRequestHandlerTrait::class
            ));
        }

        $cache = $this->cache ?? $this->cache = new SplObjectStorage();

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
        } catch (Throwable $ex) { // @phpstan-ignore-line
            throw new RuntimeException($ex->getMessage());
        }
    }

    private function classUsesInvokableTrait(string $handlerFQCN): bool
    {
        $traitFQCN = InvokableRequestHandlerTrait::class;

        $class_traits = class_uses($handlerFQCN);
        if (!empty($class_traits) && in_array($traitFQCN, $class_traits, true)) {
            return true;
        }

        $class_parents = class_parents($handlerFQCN);
        if (empty($class_parents)) {
            return false;
        }
        foreach ($class_parents as $parentFQCN) {
            $parent_traits = class_uses($parentFQCN);
            if (!empty($parent_traits) && in_array($traitFQCN, $parent_traits, true)) {
                return true;
            }
        }

        return false;
    }
}
