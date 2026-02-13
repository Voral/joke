<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Request\Exceptions;

use Vasoft\Joke\Http\Exceptions\WrongRequestMethodException as NewWrongRequestMethodException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Request\Exceptions\WrongRequestMethodException',
    'Vasoft\Joke\Http\Exceptions\WrongRequestMethodException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\Exceptions\WrongRequestMethodException instead
     */
    class WrongRequestMethodException extends NewWrongRequestMethodException {}
}
class_alias(NewWrongRequestMethodException::class, __NAMESPACE__ . '\WrongRequestMethodException');
