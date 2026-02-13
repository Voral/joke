<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core\Routing;

use Vasoft\Joke\Contract\Routing\RouteInterface as NewRouteInterface;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Contract\Core\Routing\RouteInterface',
    'Vasoft\Joke\Contract\Routing\RouteInterface',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Contract\Routing\RouteInterface instead
     */
    interface RouteInterface extends NewRouteInterface {}
}
class_alias(NewRouteInterface::class, __NAMESPACE__ . '\RouteInterface');
