<?php
require __DIR__ . '/../vendor/autoload.php';

use Vasoft\Joke\Core\Application;

$app = new Application(__DIR__, __DIR__ . '/config/routes.php');
//$app->run();