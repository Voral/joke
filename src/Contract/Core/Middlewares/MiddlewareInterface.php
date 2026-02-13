<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core\Middlewares;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface as NewMiddlewareInterface;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface',
    'Vasoft\Joke\Contract\Middleware\MiddlewareInterface',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Contract\Middleware\MiddlewareInterface instead
     */
    interface MiddlewareInterface extends NewMiddlewareInterface {}
}
class_alias(NewMiddlewareInterface::class, __NAMESPACE__ . '\MiddlewareInterface');
