<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Routing\Route as NewRoute;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Routing\Route',
    'Vasoft\Joke\Routing\Route',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use Vasoft\Joke\Routing\Route
     */
    class Route extends NewRoute {}
}
class_alias(NewRoute::class, __NAMESPACE__ . '\Route');
