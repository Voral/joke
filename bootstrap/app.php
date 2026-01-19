<?php

require __DIR__ . '/../vendor/autoload.php';

use Vasoft\Joke\Core\Application;
use Vasoft\Joke\Core\ServiceContainer;

//@todo Продумать где производить настройку и какие параметры
session_set_cookie_params([
    'samesite' => 'Lax',
    'secure' => $_SERVER['HTTPS'] ?? false,
    'httponly' => true,
    'lifetime' => 3600 * 24 * 7,
    'path' => '/',
    'domain' => '',
]);

return new Application(dirname(__DIR__), '/routes/web.php', new ServiceContainer());
