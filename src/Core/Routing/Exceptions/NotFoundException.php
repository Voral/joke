<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing\Exceptions;

use Vasoft\Joke\Routing\Exceptions\NotFoundException as NewNotFoundException;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Routing\Exceptions\NotFoundException',
    'Vasoft\Joke\Routing\Exceptions\NotFoundException',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Routing\Exceptions\NotFoundException instead
     */
    class NotFoundException extends NewNotFoundException {}
}
class_alias(NewNotFoundException::class, __NAMESPACE__ . '\NotFoundException');
