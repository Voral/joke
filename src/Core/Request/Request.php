<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Foundation\Request as NewRequest;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Request\Request',
    'Vasoft\Joke\Foundation\Request',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Foundation\Request instead
     */
    class Request extends NewRequest {}
}
class_alias(NewRequest::class, __NAMESPACE__ . '\Request');
