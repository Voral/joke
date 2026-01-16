<?php

use Vasoft\Joke\Core\Routing\Router;

/**
 * @var Router $router
 */
$router->get('/', fn() => 'Hi');
$router->get('/name/{name:slug}', fn(string $name) => 'Hi ' . $name);
$router->get('/json/{name:slug}', fn(string $name) => ['fio' => $name]);
