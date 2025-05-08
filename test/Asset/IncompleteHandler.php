<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\test\Http\Server\Asset;

use Psr\Http\Server\RequestHandlerInterface;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * Minimal Invokable Handler for unit tests
 */
class IncompleteHandler extends InvokableRequestHandler implements RequestHandlerInterface
{
    // no __invoke method implementation
}
