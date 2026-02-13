<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Http\Response\ResponseStatus as NewResponseStatus;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Response\ResponseStatus',
    'Vasoft\Joke\Http\Response\ResponseStatus',
);

class_alias(NewResponseStatus::class, __NAMESPACE__ . '\ResponseStatus');
