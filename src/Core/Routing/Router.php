<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Routing\Router as NewRouter;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Routing\Router',
    'Vasoft\Joke\Routing\Router',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use Vasoft\Joke\Routing\Router
     */
    class Router extends NewRouter {}
}
class_alias(NewRouter::class, __NAMESPACE__ . '\Router');
