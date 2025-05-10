# pine3ree invokable request handler

[![Continuous Integration](https://github.com/pine3ree/pine3ree-invokable-request-handler/actions/workflows/continuos-integration.yml/badge.svg)](https://github.com/pine3ree/pine3ree-invokable-request-handler/actions/workflows/continuos-integration.yml)

This package provides a psr server-request handler that can be invoked directly.
Descendant classes or classes using the `InvokableRequestHandlerTrait` must implement
the `__invoke` method, whose dependencies are resolved either using the request
attributes or by the container, via the composed params-resolver. A type-hinted
`ServerRequestInterface` dependency is resolved to the current request.

The default implementation requires the return type to be a psr-response object.

In scenarios where you might need to return other types, you will also need to
override the default psr `handle` method and build a response object using the
return value of the protected method `invokeHandler(ServerRequestInterface)`
which is responsible for executing the handler itself after resolving its
arguments.

## How it works

`$handler->handle($request)` calls protected method `$this->invokeHandler($request)`
`$this->invokeHandler($request)` resolve `__invoke` `$args` and calls `$this(...$args)`

## Examples

### Signature example:

```php
namespace App\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use pine3ree\Http\Server\InvokableRequestHandler;
// ...more use(s)

/**
 * An invokable controller for route '/shop/product/{id}'
 */
class MyRequestHandler extends InvokableRequestHandler // implements RequestHandlerInterface
{
    public function __invoke(
        // ServerRequestInterface $request, // this is optional...but at the end... it is a request-handler
        // type-hinted dependencies and optional named parameters matching request attributes names
    ): ResponseInterface {
        // do something with dependency and request and return a psr-response
    }
}

```

### Basic example:

```php
namespace App\Controller\Shop\Product;

use App\Model\Entity\Product;
use App\Model\ORMInterface;
use App\Session\SessionInterface;
use App\Http\Message\Response\HtmlResponse;
use App\Http\Message\Response\NotFoundResponse;
use App\View\TemplateRendererInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * An invokable controller for route '/shop/product/{id}'
 */
class Read extends InvokableRequestHandler implements RequestHandlerInterface
{
    public function __invoke(
        ServerRequestInterface $request,
        ORMInterface $orm,
        TemplateRendererInterface $view,
        SessionInterface $session, // stored as request attribute with the SessionInterface:class key
        ?int $id = null // Route-match parameter stored as request attribute with the 'id' key
    ): ResponseInterface {

        $id = $id ?? 0;
        if (id < 1) {
            return new NotFoundResponse();
        }

        $product = $orm->findById(Product::class, $id);

        if ($product === null) {
            return new NotFoundResponse("Product not found for id={$id}");
        }

        $session->set('last_visited_product_id', $id);

        return new HtmlResponse(
            $view->render('shop/product/read.html.php', [
                'product' => $product,
            ]);
        );
    }
}

```

### Same example using the provided trait when a custom constructor is needed:

```php
namespace App\Controller\Shop\Product;

use App\Model\Entity\Product;
use App\Model\ORMInterface;
use App\Session\SessionInterface;
use App\Http\Message\Response\HtmlResponse;
//..
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
//..
use pine3ree\Http\Server\InvokableRequestHandlerTrait;

class Read implements RequestHandlerInterface
{
    use InvokableRequestHandlerTrait;

    private array $options;

    public function __construct(
        ParamsResolverInterface $paramsResolver,
        array $options
    ) {
        $this->paramsResolver = $paramsResolver;
        $this->options = $options;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ORMInterface $orm,
        TemplateRendererInterface $view,
        SessionInterface $session, // stored as request attribute under the SessionInterface:class key
        ?int $id = null // Route-match parameter stored as request attribute with the 'id' key
    ): ResponseInterface {

        // Do something with $id

        // Do something using $this->options

        // Return a response
    }
}

```

### Examples of `__invoke()` not returning a response object:

```php
namespace App\Http\Server;

// ...
use App\Http\Message\Response\HtmlResponse;
use App\Http\Message\Response\JsonResponse;
use App\View\TemplateRendererInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * Example of base invokable handler that returns rendered-templates strings for
 * html-responses
 */

abstract class TemplateInvokableRequestHandler extends InvokableRequestHandler implements RequestHandlerInterface
{
    // Override the default trait implementation using the protected method `invokeHandler`
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse(
            $this->invokeHandler($request);
        );
    }

    /**
     * In this example the implementation must return strings
     *
     * public function __invoke(
     *     TemplateRendererInterface $view,
     *     // Other dependencies and/or route params here
     * ): string {
     *     // build the template $vars map here
     *     return $view->render('some/template/file.html.php', $vars);
     * }
     */
}

/**
 * Example of base invokable handler that returns arrays for json-repsonses
 */

abstract class JsonInvokableRequestHandler extends InvokableRequestHandler implements RequestHandlerInterface
{
    // Override the default trait implementation using the protected method `invokeHandler`
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(
            $this->invokeHandler($request);
        );
    }

    /**
     * In this example the implementation must return arrays
     *
     * public function __invoke(
     *     // Dependencies and/or route params here
     * ): array {
     *     // build the json-content $vars map here
     *     return $vars;
     * }
     */
}

```
