<?php

use Vasoft\Joke\Core\Routing\Router;
use Vasoft\Joke\Tests\Fixtures\Controllers\SingleController;

/**
 * @var Router $router
 */
$router->get('/shop', [SingleController::class, 'index']);
