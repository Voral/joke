<?php

require __DIR__ . '/../vendor/autoload.php';

use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Routing\Router;

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


