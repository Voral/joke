<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Collections;

use Vasoft\Joke\Collections\ReadonlyPropsCollection as NewReadonlyPropsCollection;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Collections\HeaderCollection',
    'Vasoft\Joke\Collections\ReadonlyPropsCollection',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Collections\ReadonlyPropsCollection instead
     */
    class ReadonlyPropsCollection extends NewReadonlyPropsCollection {}
}
class_alias(NewReadonlyPropsCollection::class, __NAMESPACE__ . '\ReadonlyPropsCollection');
