<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core\Routing;

use Vasoft\Joke\Contract\Routing\RouterInterface as NewRouterInterface;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Contract\Core\Routing\RouterInterface',
    'Vasoft\Joke\Contract\Routing\RouterInterface',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Contract\Routing\RouterInterface instead
     */
    interface RouterInterface extends NewRouterInterface {}
}
class_alias(NewRouterInterface::class, __NAMESPACE__ . '\RouterInterface');
