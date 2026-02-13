<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Middleware\MiddlewareDto as NewMiddlewareDto;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\MiddlewareDto',
    'Vasoft\Joke\Middleware\MiddlewareDto',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\MiddlewareDto instead
     */
    class MiddlewareDto extends NewMiddlewareDto {}
}
class_alias(NewMiddlewareDto::class, __NAMESPACE__ . '\MiddlewareDto');
