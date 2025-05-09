<?php

/**
 * @package     pine3ree-invokable-request-handler
 * @subpackage  pine3ree-invokable-request-handler-test
 * @author      pine3ree https://github.com/pine3ree
 */

declare(strict_types=1);

namespace pine3ree\test\Http\Server\Asset;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use pine3ree\Container\ParamsResolverInterface;
use pine3ree\Http\Server\InvokableRequestHandler;

/**
 * Minimal Invokable Handler for unit tests
 */
class AttributesHandler extends InvokableRequestHandler implements RequestHandlerInterface
{
    private ?ServerRequestInterface $Request = null;
    private array $args = [];

    public const YEAR = 1970;

    public function __invoke(
        ServerRequestInterface $request,
        ?int $customer_id = null,
        int $product_id = 0,
        ?string $title = null,
        string $slug = '',
        ?float $price = null,
        float $vat = 0,
        ?bool $flag = null,
        bool $truth = true,
        bool $lie = false,
        ?array $array1 = null,
        array $array2 = [],
        $uanswer = 42,
        $unullable = null
    ): ResponseInterface {

        $this->args = [
            'customer_id' => $customer_id,
            'product_id' => $product_id,
            'slug' => $slug,
            'title' => $title,
            'price' => $price,
            'vat' => $vat,
            'flag' => $flag,
            'truth' => $truth,
            'lie' => $lie,
            'price' => $price,
            'vat' => $vat,
            'array1' => $array1,
            'array2' => $array2,
            'uanswer' => $uanswer,
            'unullable' => $unullable,
        ];

        return new HtmlResponse('');
    }

    public function getParamsResolver(): ParamsResolverInterface
    {
        return $this->paramsResolver;
    }

    public function getArg(string $name)
    {
        return $this->args[$name] ?? null;
    }
}
