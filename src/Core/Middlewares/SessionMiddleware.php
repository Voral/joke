<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Middleware\SessionMiddleware as NewSessionMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\SessionMiddleware',
    'Vasoft\Joke\Middleware\SessionMiddleware',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\SessionMiddleware instead
     */
    class SessionMiddleware extends NewSessionMiddleware {}
}
class_alias(NewSessionMiddleware::class, __NAMESPACE__ . '\SessionMiddleware');
