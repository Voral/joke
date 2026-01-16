<?php

use Vasoft\Joke\Core\Application;
use Vasoft\Joke\Core\Request\HttpRequest;

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';
try {
    $app->handle(HttpRequest::fromGlobals());
} catch (Throwable $exception) {
    echo $exception->getMessage();
}