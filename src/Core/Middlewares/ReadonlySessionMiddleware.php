<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Middlewares;

use Vasoft\Joke\Middleware\ReadonlySessionMiddleware as NewReadonlySessionMiddleware;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Middlewares\ReadonlySessionMiddleware',
    'Vasoft\Joke\Middleware\ReadonlySessionMiddleware',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\ReadonlySessionMiddleware instead
     */
    class ReadonlySessionMiddleware extends NewReadonlySessionMiddleware {}
}
class_alias(NewReadonlySessionMiddleware::class, __NAMESPACE__ . '\ReadonlySessionMiddleware');
