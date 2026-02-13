<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares\Exceptions;

use Vasoft\Joke\Middleware\Exceptions\MiddlewareException as NewMiddlewareException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\Exceptions\MiddlewareException',
    'Vasoft\Joke\Middleware\Exceptions\MiddlewareException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\Exceptions\MiddlewareException instead
     */
    abstract class MiddlewareException extends NewMiddlewareException {}
}
class_alias(NewMiddlewareException::class, __NAMESPACE__ . '\MiddlewareException');
