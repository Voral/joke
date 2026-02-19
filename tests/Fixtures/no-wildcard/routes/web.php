<?php

declare(strict_types=1);

use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Tests\Fixtures\Controllers\SingleController;

/** @var Router $router */
$router->get('/shop', [SingleController::class, 'index']);
