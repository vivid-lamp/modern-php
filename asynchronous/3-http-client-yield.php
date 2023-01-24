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

class HttpClientYieldWrapper
{
    protected HttpClient $instance;
    protected Task $task;

    public function __construct(Task $task, $loop = null, $address = 'tcp://localhost:8001')
    {
        $this->task = $task;
        $this->instance = new HttpClient($loop, $address);
    }

    public function get($path): bool
    {
        $generation = $this->task->generator;
        $this->instance->get($path, static function ($response) use ($generation) {
            $generation->send($response);
        });
        return true;
    }

    public function put($path, $body): bool
    {
        $generation = $this->task->generator;
        $this->instance->put($path, $body, static function ($response) use ($generation) {
            $generation->send($response);
        });
        return true;
    }

}


class Task
{
    public Generator $generator;

    public function add(Generator $generator): void
    {
        $this->generator = $generator;
        $this->generator->current();
    }
}


$loop = new ExtEventLoop();

for ($i = 0; $i < 100; $i++):
    //pass
endfor;

$task = new Task();

$gen = (function () use ($loop, $task, $i) {
    $httpClient = new HttpClientYieldWrapper($task, $loop);

    $getResponse = yield $httpClient->get("/article/$i");

    echo $getResponse, PHP_EOL;

    $newArticle = 'new ' . $getResponse;

    $putResponse = yield $httpClient->put("/article/$i", $newArticle);

    echo $putResponse, PHP_EOL;

})();

$task->add($gen);




$loop->run();







