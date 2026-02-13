<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core;

use Vasoft\Joke\Contract\Container\ApplicationContainerInterface as NewApplicationContainerInterface;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Contract\Core\ApplicationContainerInterface',
    'Vasoft\Joke\Contract\Container\ApplicationContainerInterface',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\MiddlewareDto instead
     */
    interface ApplicationContainerInterface extends NewApplicationContainerInterface {}
}
class_alias(NewApplicationContainerInterface::class, __NAMESPACE__ . '\ApplicationContainerInterface');
