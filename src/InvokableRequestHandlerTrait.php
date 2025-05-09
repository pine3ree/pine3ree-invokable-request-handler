<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Throwable;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Helper\Reflection;

use function is_callable;
use function is_scalar;
use function filter_var;
use function sprintf;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;

/**
 * InvokableRequestHandlerTrait composes a ParamsResolver instance and implements
 * a specific version of the RequestHandlerInterface::handle() method that proxies
 * to the `__invoke` method using method injection for its dependencies.
 *
 * The internal params-resolver is what performs method-injection onto `__invoke`
 */
trait InvokableRequestHandlerTrait
{
    protected ParamsResolverInterface $paramsResolver;

    /**
     * @var bool $typecastRequestAttributes Flag to enable/disable type-casting
     *      on scalar request attributes. Should be enabled if strict_types are
     *      used as route parameters are usually resolved as string|null
     */
    protected bool $typecastRequestAttributes = true;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->invokeHandler($request);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        throw new RuntimeException(sprintf(
            'The `%s::__invoke(...)` method did not return a `%s` instance',
            static::class,
            ResponseInterface::class
        ));
    }

    /**
     * Make sure that the `__invoke()` is implemented, and then call it injecting
     * its dependencies fetched from the request attributes and/or from the container
     *
     * Override this method if your `__invoke()` implementation does not return
     * a Response. You may for instance accept a string return value and use it
     * as the response body or an array return value to build and return a json
     * response or to automatically render a template into a response body
     *
     * @param ServerRequestInterface $request
     * @return mixed
     * @throws RuntimeException
     */
    protected function invokeHandler(ServerRequestInterface $request)
    {
        if (is_callable($this)) {
            // Build params to be injected as resolved arguments
            $resolvedParams = [
                ServerRequestInterface::class => $request, // Inject the request object
            ] + $this->typecastScalarAttributes($request->getAttributes());

            try {
                // Resolve the arguments for the __invoke() method
                $args = $this->paramsResolver->resolve($this, $resolvedParams);
                return empty($args) ? $this() : $this(...$args);
            } catch (Throwable $ex) {
                throw new RuntimeException($ex->getMessage());
            }
        }

        throw new RuntimeException(sprintf(
            'The invokable-handler method `%s::__invoke(...)` is not implemented!',
            static::class
        ));
    }

    /**
     * Perform type-casting of scalar request-attributes based on type-hinting
     * of corresponding parameters of the `__invoke` method
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    protected function typecastScalarAttributes(array $attributes): array
    {
        if (empty($attributes)) {
            return [];
        }

        /** @var ReflectionParameter[] $reflectionParams */
        $reflectionParams = Reflection::getParametersForInvokable($this);

        foreach ($reflectionParams as $rp) {
            if (!$rp->hasType()) {
                continue;
            }

            $name = $rp->getName();
            // NULL values are discarded
            $value = $attributes[$name] ?? null;
            if (!isset($value)) {
                continue;
            }

            $type = $rp->getType();
            // Only perform typecasting for parameters with single optionally nullable type-hint
            if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                $attributes[$name] = $this->typecastValue($type->getName(), $value);
            }
        }

        return $attributes;
    }

    /**
     * Typecast value of known php built-in scalar types: string, int, float, bool
     *
     * @param string $php_type
     * @param mixed $value
     * @return mixed
     */
    protected function typecastValue(string $php_type, $value)
    {
        if ('string' === $php_type) {
            return is_scalar($value) ? (string)$value : $value;
        }
        if ('int' === $php_type) {
            return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        }
        if ('float' === $php_type) {
            return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        }
        if ('bool' === $php_type) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return $value;
    }
}
