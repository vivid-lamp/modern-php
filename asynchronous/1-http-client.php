<?php

use React\EventLoop\ExtEventLoop;

require __DIR__ . '/../vendor/autoload.php';


class HttpClient
{
    public function __construct(
        public ?ExtEventLoop $loop = null,
        protected string $address = 'tcp://localhost:8001',
    ){
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
            $that->loop->addReadStream($client, function($client) use ($cb, $that) {
                $that->loop->removeReadStream($client);
                $response = fread($client, 1024);
                preg_match('/(?P<body>[^\n\r]*)$/', $response, $matches);
                $cb($matches['body']);
            });
        });
    }
}

$httpClient = new HttpClient();
$httpClient->get('/article/1', function($response) use ($httpClient) {
    echo $response, PHP_EOL;
    $httpClient->put('/article/1', 'new article content.', function($response) {
        echo $response, PHP_EOL;
    });
});

$httpClient->loop->run();







