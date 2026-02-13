<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Http\Response\JsonResponse as NewJsonResponse;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Response\JsonResponse',
    'Vasoft\Joke\Http\Response\JsonResponse',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\Response\JsonResponse instead
     */
    class JsonResponse extends NewJsonResponse {}
}
class_alias(NewJsonResponse::class, __NAMESPACE__ . '\JsonResponse');
