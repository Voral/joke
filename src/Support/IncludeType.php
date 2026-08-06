<?php

declare(strict_types=1);

namespace Vasoft\Joke\Support;

/**
 * Типы подключения файлов в сервисе FileSystem.
 *
 * Используется вместо передачи языковой конструкции как параметра,
 * поскольку include/require являются language constructs и не могут
 * быть переданы в переменную или callback.
 *
 * @see FileSystem::doInclude
 */
enum IncludeType: string
{
    case INCLUDE = 'include';
    case INCLUDE_ONCE = 'include_once';
    case REQUIRE = 'require';
    case REQUIRE_ONCE = 'require_once';
}
