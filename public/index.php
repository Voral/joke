<?php

use Vasoft\Joke\Application\Application;
use Vasoft\Joke\Http\HttpRequest;

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handle(HttpRequest::fromGlobals());
