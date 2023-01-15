<?php

namespace PsrImplement\PSR15\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Foo implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        echo 'Before Foo.', PHP_EOL;
        $response = $handler->handle($request);
        echo 'After Foo.', PHP_EOL;
        return $response;
    }
}