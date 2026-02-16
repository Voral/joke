<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Middleware\ExceptionMiddleware as NewExceptionMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\ExceptionMiddleware',
    'Vasoft\Joke\Middleware\ExceptionMiddleware',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\ExceptionMiddleware instead
     */
    class ExceptionMiddleware extends NewExceptionMiddleware {}
}
class_alias(NewExceptionMiddleware::class, __NAMESPACE__ . '\ExceptionMiddleware');
