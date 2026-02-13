<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Container\ParameterResolver as NewParameterResolver;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Routing\ParameterResolver',
    'Vasoft\Joke\Container\ParameterResolver',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Container\ParameterResolver instead
     */
    class ParameterResolver extends NewParameterResolver {}
}
class_alias(NewParameterResolver::class, __NAMESPACE__ . '\ParameterResolver');
