<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Exceptions\Http;
use Vasoft\Joke\Middleware\Exceptions\CsrfMismatchException as NewCsrfMismatchException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Exceptions\Http\CsrfMismatchException',
    'Vasoft\Joke\Middleware\Exceptions\CsrfMismatchException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Middleware\Exceptions\CsrfMismatchException instead
     */
    abstract class CsrfMismatchException extends NewCsrfMismatchException {}
}
class_alias(NewCsrfMismatchException::class, __NAMESPACE__ . '\CsrfMismatchException');

