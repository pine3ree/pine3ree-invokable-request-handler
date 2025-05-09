<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\Http\Server;

use Psr\Http\Server\RequestHandlerInterface;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandlerTrait;

/**
 * InvokableRequestHandler represents a request-handler whose `__invoke` method is automatically
 * called via by `handle` method. The `__invoke` method can be implemented using
 * method injection.
 */
abstract class InvokableRequestHandler implements RequestHandlerInterface
{
    use InvokableRequestHandlerTrait;

    /**
     * The default constructor implementation to be used along with the
     * InvokableRequestHandlerFactory only accepts a ParamsResolver argument
     *
     * For more complex scenarios, e.g. with multiple constructor dependencies,
     * be sure to include the ParamsResolver dependency and an custom or more
     * approriate factory such as the eflection factory
     *
     * @param ParamsResolverInterface $paramsResolver
     * @param bool $typecastRequestAttributes Flag to enable/disable type-casting
     *      on scalar request attributes
     */
    public function __construct(
        ParamsResolverInterface $paramsResolver,
        bool $typecastRequestAttributes = true
    ) {
        $this->paramsResolver = $paramsResolver;
        $this->typecastRequestAttributes = $typecastRequestAttributes;
    }
}
