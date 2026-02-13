<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares\Exceptions;

use Vasoft\Joke\Middleware\Exceptions\WrongMiddlewareException as NewWrongMiddlewareException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\Exceptions\WrongMiddlewareException',
    'Vasoft\Joke\Middleware\Exceptions\WrongMiddlewareException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\Exceptions\WrongMiddlewareException instead
     */
    abstract class WrongMiddlewareException extends NewWrongMiddlewareException {}
}
class_alias(NewWrongMiddlewareException::class, __NAMESPACE__ . '\WrongMiddlewareException');
