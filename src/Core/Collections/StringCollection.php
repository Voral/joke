<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Collections;
use Vasoft\Joke\Collections\StringCollection as NewStringCollection;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Collections\HeaderCollection',
    'Vasoft\Joke\Collections\StringCollection',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Collections\StringCollection instead
     */
    class StringCollection extends NewStringCollection {}
}
class_alias(NewStringCollection::class, __NAMESPACE__ . '\StringCollection');
