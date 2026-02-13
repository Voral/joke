<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Http\Response\Response as NewResponse;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Response\Response',
    'Vasoft\Joke\Http\Response\Response',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\Response\Response instead
     */
    abstract class Response extends NewResponse {}
}
class_alias(NewResponse::class, __NAMESPACE__ . '\Response');
