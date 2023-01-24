<?php

namespace PsrImplement\PSR15;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class RequestHandler implements RequestHandlerInterface
{
    /** @var Iterable<MiddlewareInterface> */
    protected $queue;

    public function __construct(Iterable $queue)
    {
        $this->queue = $queue;
    }
                                            
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->queue);
        if ($middleware === false) {
            $response = new Response();
            $response = $response->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('All middlewares processed.');
            return $response;
        }
        next($this->queue);
        return $middleware->process($request, $this);
    }
}
