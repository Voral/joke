<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Http\HttpRequest as NewHttpRequest;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'VVasoft\Joke\Core\Request\HttpRequest',
    'Vasoft\Joke\Http\HttpRequest',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\HttpRequest instead
     */
    class HttpRequest extends NewHttpRequest {}
}
class_alias(NewHttpRequest::class, __NAMESPACE__ . '\HttpRequest');
