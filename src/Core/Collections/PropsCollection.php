<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Collections;

use Vasoft\Joke\Collections\PropsCollection as NewPropsCollection;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Collections\HeaderCollection',
    'Vasoft\Joke\Collections\PropsCollection',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Collections\PropsCollection instead
     */
    class PropsCollection extends NewPropsCollection {}
}
class_alias(NewPropsCollection::class, __NAMESPACE__ . '\PropsCollection');
