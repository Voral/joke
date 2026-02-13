<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Http\HttpMethod as NewHttpMethod;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Request\HttpMethod',
    'Vasoft\Joke\Http\HttpMethod',
);

class_alias(NewHttpMethod::class, __NAMESPACE__ . '\HttpMethod');
