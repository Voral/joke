<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Routing\StdGroup as NewStdGroup;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Routing\StdGroup',
    'Vasoft\Joke\Routing\StdGroup',
);

class_alias(NewStdGroup::class, __NAMESPACE__ . '\StdGroup');
