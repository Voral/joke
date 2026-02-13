<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing\Exceptions;

use Vasoft\Joke\Container\Exceptions\AutowiredException as NewAutowiredException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Routing\Exceptions\AutowiredException',
    'Vasoft\Joke\Container\Exceptions\AutowiredException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Container\Exceptions\AutowiredException instead
     */
    class AutowiredException extends NewAutowiredException {}
}
class_alias(NewAutowiredException::class, __NAMESPACE__ . '\AutowiredException');
