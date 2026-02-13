<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Application\Application as NewApplication;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Application',
    'Vasoft\Joke\Application',
    '1.2.0',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Application\Application instead
     */
    class Application extends NewApplication {}
}
class_alias(NewApplication::class, __NAMESPACE__ . '\Application');
