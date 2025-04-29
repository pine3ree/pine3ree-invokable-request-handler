# pine3ree invokable request handler

[![Continuous Integration](https://github.com/pine3ree/pine3ree-invokable-request-handler/actions/workflows/continuos-integration.yml/badge.svg)](https://github.com/pine3ree/pine3ree-invokable-request-handler/actions/workflows/continuos-integration.yml)

This package provides a psr server-request handler that can be invoked directly.
Descendant classes or classes using the `InvokableRequestHandlerTrait` must implement
the `__invoke` method, whose dependencies are resolved either using the request
attributes or by the container, via the composed params-resolver. A type-hinted
`ServerRequestInterface` dependency is resolved to the current request.
