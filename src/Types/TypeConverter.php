<?php

declare(strict_types=1);

namespace Vasoft\Joke\Types;

use Vasoft\Joke\Support\Types\TypeConverter as NewTypeConverter;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Types\TypeConverter',
    'Vasoft\Joke\Support\Types\TypeConverter',
);

class_alias(NewTypeConverter::class, __NAMESPACE__ . '\TypeConverter');
