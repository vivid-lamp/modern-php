<?php


namespace PsrImplement\PSR11;

use Illuminate\Container\Container;
use PsrImplement\PSR11\Service\ApiFetch;

require __DIR__ . '/../../vendor/autoload.php';


$container = new Container();

// 绑定类到容器
$container->singleton(ApiFetch::class);

class Foo
{

    /** @var ApiFetch */
    protected $apiFetch;

    public function __construct(ApiFetch $apiFetch)
    {
        $this->apiFetch = $apiFetch;
    }

    public function api()
    {
        return $this->apiFetch->success('msg from api.', ['data from api.']);
    }
}


class Bar
{
    public function injectToMethod(ApiFetch $apiFetch)
    {
        return $apiFetch->success('msg from injectToMethod.', ['data from injectToMethod.']);
    }
}

if (strtolower($_SERVER['REQUEST_URI']) != '/injecttomethod') {
    /** @var Foo $foo */
    $foo = $container->make(Foo::class);
    $response = $foo->api();
} else {
    $bar = new Bar();
    $response = $container->call([$bar, 'injectToMethod']);
}


foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody();
