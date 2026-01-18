<?php

use Vasoft\Joke\Tests\Fixtures\Controllers\InvokeController;
use Vasoft\Joke\Core\Routing\Router;

/**
 * @var Router $router
 */
$router->get('/', fn() => <<<HTML
<ul>
    <li><a href="/name/Alex">Hi Alex</a> Текстовый ответ. Имя можно менять</li>
    <li><a href="/json/Alex">Hi Alex</a> Json ответ. Имя можно менять</li>
    <li><a href="/invoke/property">__Invoke</a></li>
</ul>
HTML
);
$router->get('/name/{name:slug}', fn(string $name) => 'Hi ' . $name);
$router->get('/json/{name:slug}', fn(string $name) => ['fio' => $name]);
$router->get('/invoke/{prop}', InvokeController::class);
// @todo Интерфейс, публичный/статический метод
// @todo Класс, публичный/статический метод
