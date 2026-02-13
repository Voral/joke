<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Collections;

use Vasoft\Joke\Collections\HeadersCollection as NewHeadersCollection;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Collections\HeaderCollection',
    'Vasoft\Joke\Collections\HeadersCollection',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Collections\HeadersCollection instead
     */
    class HeadersCollection extends NewHeadersCollection {}
}
class_alias(NewHeadersCollection::class, __NAMESPACE__ . '\HeadersCollection');
