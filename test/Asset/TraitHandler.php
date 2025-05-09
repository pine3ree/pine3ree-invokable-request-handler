<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\test\Http\Server\Asset;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use pine3ree\Http\Server\InvokableRequestHandlerTrait;

/**
 * Handler using trait
 */
class TraitHandler implements RequestHandlerInterface
{
    use InvokableRequestHandlerTrait;

    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse('');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this($request);
    }
}
