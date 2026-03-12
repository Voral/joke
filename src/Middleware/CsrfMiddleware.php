<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware;

use Vasoft\Joke\Http\Csrf\CsrfMiddleware as NewCsrfMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\CsrfMiddleware',
    'Vasoft\Joke\Http\Csrf\CsrfMiddleware',
);

/** @phpstan-ignore  if.alwaysFalse */
if (false) {
    /**
     * @deprecated since 1.3.0, use \Vasoft\Joke\Http\CsrfMiddleware instead
     */
    class CsrfMiddleware extends NewCsrfMiddleware {}
}
class_alias(NewCsrfMiddleware::class, __NAMESPACE__ . '\CsrfMiddleware');
