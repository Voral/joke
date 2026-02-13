<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Middleware\StdMiddleware as NewStdMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\StdMiddleware',
    'Vasoft\Joke\Middleware\StdMiddleware',
);

class_alias(NewStdMiddleware::class, __NAMESPACE__ . '\StdMiddleware');
