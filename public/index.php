<?php

use Vasoft\Joke\Core\Application;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Router;
use Vasoft\Joke\Core\ServiceContainer;

$app = require_once __DIR__ . '/../bootstrap/app.php';

class A
{
    public function __construct()
    {
        echo 'A created';
    }
}

class B
{
    public function __construct(A $a)
    {
        echo 'B created';
    }
}

try {
    /** @var Application $app */
    $serviceContainer = new ServiceContainer();
    $serviceContainer->registerSingleton(\A::class, A::class);
    $serviceContainer->registerSingleton(\B::class, B::class);

    $serviceContainer->get(B::class);
} catch (Throwable $e) {
    echo $e->getMessage();
    die();
}

die();
$request = HttpRequest::fromGlobals();
$router = new Router();
$examples = [];
$rout = $router->get('/', function () {
    return 'Root';
});
$examples[$rout->path] = $rout->compiledPattern;
$rout = $router->get('/test/{example}', function (string $example) {
    return 'With one params ' . $example;
});
$examples[$rout->path] = $rout->compiledPattern;
$rout = $router->get(
    '/test/{example}/{method}/{name:slug}',
    function (string $example, \Vasoft\Joke\Core\Request\HttpMethod $method, string $name): string {
        return 'With more params ' . $example . ' ' . $method->value . ' ' . $name;
    }
);
$examples[$rout->path] = $rout->compiledPattern;

$result = $router->dispatch($request);

echo '<pre>';
print_r($result);
echo '</pre>';



