<?php

namespace PsrImplement\PSR15\Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Bar implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        echo 'Before Bar.', PHP_EOL;
        $response = $handler->handle($request);
        echo 'After Bar.', PHP_EOL;
        return $response;
    }
}