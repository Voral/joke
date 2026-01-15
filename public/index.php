<?php

require __DIR__ . '/../vendor/autoload.php';

use Vasoft\Joke\Core\Request\HttpRequest;

$request = HttpRequest::fromGlobals();
print_r($request->server->getAll());
print_r($request->headers->getAll());

