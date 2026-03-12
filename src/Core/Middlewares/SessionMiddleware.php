<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Http\Middleware\SessionMiddleware as NewSessionMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\SessionMiddleware',
    'Vasoft\Joke\Http\Middleware\SessionMiddleware',
);

/** @phpstan-ignore  if.alwaysFalse */
if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\Middleware\SessionMiddleware instead
     */
    class SessionMiddleware extends NewSessionMiddleware {}
}
class_alias(NewSessionMiddleware::class, __NAMESPACE__ . '\SessionMiddleware');
