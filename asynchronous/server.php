<?php

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Promise\Deferred;

require __DIR__ . '/../vendor/autoload.php';

$http = new HttpServer(function (ServerRequestInterface $request) {
    $path = $request->getUri()->getPath();
    preg_match("/^\/article\/(?P<id>\d+)$/", $path, $matches);
    $id = $matches['id'] ?? 0;

    $deferred = new Deferred();
    Loop::addTimer(1, function () use ($deferred) {
        $deferred->resolve();
    });

    $body = '';
    if ($request->getMethod() === 'GET') {
        $body = sprintf("%3d: article content %s", $id, chr(random_int(65, 122)));
    } elseif ($request->getMethod() === "PUT") {
        $body = $request->getBody();
    }

    return $deferred->promise()->then(function () use ($body) {
        return React\Http\Message\Response::plaintext($body);
    });
});

$socket = new React\Socket\SocketServer('0.0.0.0:8001');
$http->listen($socket);
