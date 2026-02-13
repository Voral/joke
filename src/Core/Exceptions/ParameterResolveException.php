<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Exceptions;

use Vasoft\Joke\Container\Exceptions\ParameterResolveException as NewParameterResolveException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Exceptions\ParameterResolveException',
    'Vasoft\Joke\Container\Exceptions\ParameterResolveException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Container\Exceptions\ParameterResolveException instead
     */
    class ParameterResolveException extends NewParameterResolveException {}
}
class_alias(NewParameterResolveException::class, __NAMESPACE__ . '\ParameterResolveException');
