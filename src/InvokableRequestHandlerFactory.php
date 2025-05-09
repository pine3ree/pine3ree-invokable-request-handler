<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\Http\Server;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use SplObjectStorage;
use Throwable;
use pine3ree\Container\ParamsResolver;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;
use pine3ree\Http\Server\InvokableRequestHandlerTrait;

use function class_exists;
use function class_parents;
use function class_uses;
use function in_array;
use function is_subclass_of;

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

        $ifaceFQCN = RequestHandlerInterface::class;
        $baseFQCN  = InvokableRequestHandler::class;
        $traitFQCN = InvokableRequestHandlerTrait::class;

        if (!is_subclass_of($handlerFQCN, $ifaceFQCN)
            || !$this->classUsesInvokableTrait($handlerFQCN)
        ) {
            throw new RuntimeException(
                "{$handlerFQCN} must be either a subclass of `{$baseFQCN}` or"
                . " implement `{$ifaceFQCN}` using the trait `{$traitFQCN}`."
            );
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
