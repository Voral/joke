<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware;

use Vasoft\Joke\Http\Middleware\SessionMiddleware as NewSessionMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\SessionMiddleware',
    'Vasoft\Joke\Middleware\SessionMiddleware',
);

/** @phpstan-ignore  if.alwaysFalse */
if (false) {
    /**
     * @deprecated since 1.3.0, use \Vasoft\Joke\Middleware\SessionMiddleware instead
     */
    class SessionMiddleware extends NewSessionMiddleware {}
}
class_alias(NewSessionMiddleware::class, __NAMESPACE__ . '\SessionMiddleware');
