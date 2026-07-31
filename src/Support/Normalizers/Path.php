<?php

declare(strict_types=1);

namespace Vasoft\Joke\Support\Normalizers;

use Vasoft\Joke\Application\FileSystem;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Support\Normalizers\Path',
    'Vasoft\Joke\Application\FileSystem',
);

/** @phpstan-ignore  if.alwaysFalse */
if (false) {
    /**
     * @deprecated since 1.5.0, use \Vasoft\Joke\Http\CsrfMiddleware instead
     */
    class Path extends FileSystem {}
}
class_alias(FileSystem::class, Path::class);
