<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Exceptions;

use Vasoft\Joke\Session\Exceptions\SessionException as NewSessionException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Exceptions\SessionException',
    'Vasoft\Joke\Session\Exceptions\SessionException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Session\Exceptions\SessionException instead
     */
    class SessionException extends NewSessionException {}
}
class_alias(NewSessionException::class, __NAMESPACE__ . '\SessionException');
