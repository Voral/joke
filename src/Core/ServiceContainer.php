<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Container\ServiceContainer as NewServiceContainer;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\ServiceContainer',
    'Vasoft\Joke\Container\ServiceContainer',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use Vasoft\Joke\Container\ServiceContainer instead
     */
    class ServiceContainer extends NewServiceContainer {}
}
class_alias(NewServiceContainer::class, __NAMESPACE__ . '\ServiceContainer');
