# pine3ree invokable request handler

[![Continuous Integration](https://github.com/pine3ree/pine3ree-invokable-request-handler/actions/workflows/continuos-integration.yml/badge.svg)](https://github.com/pine3ree/pine3ree-invokable-request-handler/actions/workflows/continuos-integration.yml)

This package provides a psr server-request handler that can be invoked directly.
Descendant classes or classes using the `InvokableRequestHandlerTrait` must implement
the `__invoke` method, whose dependencies are resolved either using the request
attributes or by the container, via the composed params-resolver. A type-hinted
`ServerRequestInterface` dependency is resolved to the current request.

Example:

```php
<?php

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
class Read extends InvokableRequestHandler implements RequestHandlerInterface;
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

Same example using the provided trait when a custom constructor is needed:

```php
<?php

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

class Read implements RequestHandlerInterface;
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

