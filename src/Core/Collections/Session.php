<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Collections;

use Vasoft\Joke\Session\SessionCollection;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Collections\Session',
    'Vasoft\Joke\Session\SessionCollection',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use Vasoft\Joke\Session\SessionCollection instead
     */
    abstract class Session extends SessionCollection {}
}
class_alias(SessionCollection::class, __NAMESPACE__ . '\Session');
