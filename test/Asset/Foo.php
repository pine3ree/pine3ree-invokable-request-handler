<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\test\Http\Server\Asset;

/**
 * Class Foo
 */
class Foo
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
