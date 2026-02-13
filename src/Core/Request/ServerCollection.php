<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Http\ServerCollection as NewServerCollection;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Request\ServerCollection',
    'Vasoft\Joke\Http\ServerCollection',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\ServerCollection instead
     */
    class ServerCollection extends NewServerCollection {}
}
class_alias(NewServerCollection::class, __NAMESPACE__ . '\ServerCollection');
