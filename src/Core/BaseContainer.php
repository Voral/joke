<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core;

use Vasoft\Joke\Container\BaseContainer as NewBaseContainer;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\BaseContainer',
    'Vasoft\Joke\Container\BaseContainer',
    '1.2.0',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Application\Application instead
     */
    class BaseContainer extends NewBaseContainer {}
}
class_alias(NewBaseContainer::class, __NAMESPACE__ . '\BaseContainer');
