<?php

/**
 * @package pine3ree-invokable-request-handler
 * @author  pine3ree https://github.com/pine3ree
 */

namespace pine3ree\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;
use pine3ree\Container\ParamsResolverInterface;

use function is_callable;
use function sprintf;

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
            ] + $request->getAttributes();

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
}
