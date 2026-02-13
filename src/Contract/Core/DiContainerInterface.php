<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core;

use Vasoft\Joke\Contract\Container\DiContainerInterface as NewDiContainerInterface;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Contract\Core\DiContainerInterface',
    'Vasoft\Joke\Contract\Container\DiContainerInterface',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\MiddlewareDto instead
     */
    interface DiContainerInterface extends NewDiContainerInterface {}
}
class_alias(NewDiContainerInterface::class, __NAMESPACE__ . '\DiContainerInterface');
