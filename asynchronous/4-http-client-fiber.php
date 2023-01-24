<?php

use React\EventLoop\ExtEventLoop;

require __DIR__ . '/../vendor/autoload.php';


class HttpClient
{
    public function __construct(
        public ?ExtEventLoop $loop = null,
        protected string     $address = 'tcp://localhost:8001',
    )
    {
        $this->loop || $this->loop = new ExtEventLoop();
    }

    public function get(string $path, Closure $cb): void
    {
        $request = "GET $path HTTP/1.0\r\n\r\n";
        $this->request($request, $cb);
    }

    public function put(string $path, string $body, Closure $cb): void
    {
        $contentLength = strlen($body);
        $request = "PUT $path HTTP/1.0\r\nContent-Type: text/plain\r\nContent-Length: $contentLength\r\n\r\n$body";
        $this->request($request, $cb);
    }

    protected function request($request, $cb): void
    {
        $client = stream_socket_client($this->address, $error_code, $error_message);
        $that = $this;
        $this->loop->addWriteStream($client, static function ($client) use ($that, $cb, $request) {
            $that->loop->removeWriteStream($client);
            fwrite($client, $request);
            $that->loop->addReadStream($client, function ($client) use ($cb, $that) {
                $that->loop->removeReadStream($client);
                $response = fread($client, 1024);
                preg_match('/(?P<body>[^\n\r]*)$/', $response, $matches);
                $cb($matches['body']);
            });
        });
    }
}

class HttpClientFiberWrapper
{
    protected HttpClient $instance;

    public function __construct($loop = null, $address = 'tcp://localhost:8001')
    {
        $this->instance = new HttpClient($loop, $address);
    }

    public function get($path)
    {
        $fiber = Fiber::getCurrent();
        $this->instance->get($path, static function ($response) use ($fiber) {
            $fiber->resume($response);
        });
        return Fiber::suspend();
    }

    public function put($path, $body)
    {
        $fiber = Fiber::getCurrent();
        $this->instance->put($path, $body, static function ($response) use ($fiber) {
            $fiber->resume($response);
        });
        return Fiber::suspend();
    }

}



$loop = new ExtEventLoop();

for ($i = 0; $i < 1000; $i++):
    //pass
endfor;

$fiber = new Fiber(function () use ($loop, $i) {
    $httpClient = new HttpClientFiberWrapper($loop);

    $getResponse = $httpClient->get("/article/$i");

    echo $getResponse, PHP_EOL;

    $newArticle = 'new ' . $getResponse;

    $putResponse = $httpClient->put("/article/$i", $newArticle);

    echo $putResponse, PHP_EOL;

});

$fiber->start();



$loop->run();







