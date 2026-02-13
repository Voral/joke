<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Middleware\MiddlewareCollection as NewMiddlewareCollection;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\MiddlewareCollection',
    'Vasoft\Joke\Middleware\MiddlewareCollection',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\MiddlewareCollection instead
     */
    class MiddlewareCollection extends NewMiddlewareCollection {}
}
class_alias(NewMiddlewareCollection::class, __NAMESPACE__ . '\MiddlewareCollection');
