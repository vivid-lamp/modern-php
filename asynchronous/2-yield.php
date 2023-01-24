<?php

$generation = (function () {
    $v1 = yield 'a';
    echo $v1, PHP_EOL;
    $v2 = yield 'b';
    echo $v2, PHP_EOL;
    $v3 = yield 'c';
    echo $v3, PHP_EOL;
})();


echo $generation->current(), PHP_EOL;

echo $generation->send(1), PHP_EOL;
echo $generation->send(2), PHP_EOL;
$generation->send(3);

// 执行结果：
// a
// 1
// b
// 2
// c
// 3