<?php

class MyExtEventLoop
{
    public EventBase $eventBase;

    /** @var Event[] */
    private $events = [];

    /** @var resource[] */
    private $readRefs = [];

    public function __construct()
    {
        $config = new EventConfig();
        $this->eventBase = new EventBase($config);
    }

    public function addReadStream($stream, $listener): void
    {
        $event = new Event($this->eventBase, $stream, Event::PERSIST | Event::READ, $listener);
        $event->add();
        $key = intval($stream);
        $this->events[$key] = $event;
        /** 不保存stream会丢失 */
        if (\PHP_VERSION_ID >= 70000) {
            $this->readRefs[$key] = $stream;
        }
    }

    public function removeReadStream($stream): void
    {
        $key = intval($stream);
        if (isset($this->events[$key])) {
            $this->events[$key]->free();
            unset($this->events[$key], $this->readRefs[$key]);
        }
    }

    public function run(): void
    {
        $this->eventBase->loop();
    }
}

$server = stream_socket_server('tcp://0.0.0.0:8100');
stream_set_blocking($server, false);

$loop = new MyExtEventLoop();

$loop->addReadStream($server, function ($server) use ($loop) {
    $conn = stream_socket_accept($server);
    $loop->addReadStream($conn, function ($conn) use ($loop) {
        echo fread($conn, 1000);
        $data = "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nConnection: closed\r\nContent-Length: 5\r\n\r\nHello";
        fwrite($conn, $data);
        $loop->removeReadStream($conn);
        fclose($conn);
    });
});

$loop->eventBase->loop();