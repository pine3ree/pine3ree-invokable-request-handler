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
use function class_uses;
use function in_array;
use function is_bool;
use function is_subclass_of;

/**
 * A generic factory for invokable-handlers whose constructors only accepts a
 * single argument of type ParamsResolverInterface and optionally the request-attributes
 * typecasting flag
 */
class InvokableRequestHandlerFactory
{
    /**
     * Params resolvers cached by container
     * @var SplObjectStorage<ContainerInterface, ParamsResolverInterface>|null
     */
    private ?SplObjectStorage $cache = null;

    public const TYPECAST_FLAG = 'typecast_request_attributes';

    /**
     *
     * @param ContainerInterface $container
     * @param string $handlerFQCN The handler fully qualified class name
     * @param array<string, mixed>|null $options
     * @return InvokableRequestHandler|RequestHandlerInterface
     * @throws RuntimeException
     */
    public function __invoke(ContainerInterface $container, string $handlerFQCN, ?array $options = null): RequestHandlerInterface
    {
        if (!class_exists($handlerFQCN)) {
            throw new RuntimeException(
                "Unable to load the requested class {$handlerFQCN}"
            );
        }

        $ifaceFQCN = RequestHandlerInterface::class;
        $baseFQCN  = InvokableRequestHandler::class;
        $traitFQCN = InvokableRequestHandlerTrait::class;

        if (!is_subclass_of($handlerFQCN, $baseFQCN)
            && !(is_subclass_of($handlerFQCN, $ifaceFQCN)
                && in_array($traitFQCN, class_uses($handlerFQCN), true)
            )
        ) {
            throw new RuntimeException(
                "{$handlerFQCN} must be either a subclass of `{$baseFQCN}` or"
                . " implement `{$ifaceFQCN}` using the trait `{$traitFQCN}`."
            );
        }

        $cache = $this->cache ?? $this->cache = new SplObjectStorage();

        /** @var SplObjectStorage<ContainerInterface, ParamsResolverInterface> $cache Previous line ensures this */
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

        $typecastRequestAttributes = $options[self::TYPECAST_FLAG] ?? null;

        try {
            if (is_bool($typecastRequestAttributes)) {
                return new $handlerFQCN($paramsResolver, $typecastRequestAttributes);
            }
            return new $handlerFQCN($paramsResolver);
        } catch (Throwable $ex) {
            throw new RuntimeException($ex->getMessage());
        }
    }
}
