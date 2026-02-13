<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Exceptions;

use Vasoft\Joke\Exceptions\InvalidArgumentException as NewInvalidArgumentException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Exceptions\InvalidArgumentException',
    'Vasoft\Joke\Exceptions\InvalidArgumentException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Exceptions\InvalidArgumentException instead
     */
    class InvalidArgumentException extends NewInvalidArgumentException {}
}
class_alias(NewInvalidArgumentException::class, __NAMESPACE__ . '\InvalidArgumentException');
