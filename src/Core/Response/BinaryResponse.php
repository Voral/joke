<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Http\Response\BinaryResponse as NewBinaryResponse;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Response\BinaryResponse',
    'Vasoft\Joke\Http\Response\BinaryResponse',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\Response\BinaryResponse instead
     */
    abstract class BinaryResponse extends NewBinaryResponse {}
}
class_alias(NewBinaryResponse::class, __NAMESPACE__ . '\BinaryResponse');
