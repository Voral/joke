<?php

use Vasoft\Joke\Core\Application;
use Vasoft\Joke\Core\Request\HttpRequest;

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handle(HttpRequest::fromGlobals());
