<?php

use PsrImplement\PSR14\EventDispatcher;
use PsrImplement\PSR14\ListenerProvider;

require __DIR__ . '/../../vendor/autoload.php';

header('Content-type: text/plain');

$listenerProvider = new ListenerProvider();

// 分发器
$dispatcher = new EventDispatcher($listenerProvider);

// 登出事件
class LogoutEvent {}

// 监听器 A
$listenerA = new class {
    public function __invoke($event)
    {
        echo 'Listener A: Session destroyed.', PHP_EOL;
    }
};

$listenerSet = new class {

    // 监听器 B
    public function listenerB($event) {
        echo 'Listener B: Session destroyed.', PHP_EOL;
    }
    // 监听器 C
    public function listenerC($event) {
        echo 'Listener C: Session destroyed.', PHP_EOL;
    }
};

// 监听事件
$listenerProvider->listen(LogoutEvent::class, $listenerA);
$listenerProvider->listen(LogoutEvent::class, [$listenerSet, 'listenerB']);
$listenerProvider->listen(LogoutEvent::class, [$listenerSet, 'listenerC']);

// 发射器
$dispatcher->dispatch(new LogoutEvent());



