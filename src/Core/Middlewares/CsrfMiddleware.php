<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Http\Middleware\CsrfMiddleware as NewCsrfMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\CsrfMiddleware',
    'Vasoft\Joke\Http\Middleware\CsrfMiddleware',
);

/** @phpstan-ignore  if.alwaysFalse */
if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\Middleware\CsrfMiddleware instead
     */
    class CsrfMiddleware extends NewCsrfMiddleware {}
}
class_alias(NewCsrfMiddleware::class, __NAMESPACE__ . '\CsrfMiddleware');
