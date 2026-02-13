<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Exceptions;

use Vasoft\Joke\Exceptions\JokeException as NewJokeException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Exceptions\JokeException',
    'Vasoft\Joke\Exceptions\JokeException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Exceptions\JokeException instead
     */
    class JokeException extends NewJokeException {}
}
class_alias(NewJokeException::class, __NAMESPACE__ . '\JokeException');
