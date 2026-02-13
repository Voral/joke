<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Core\Routing;

use Vasoft\Joke\Contract\Container\ResolverInterface as NewResolverInterface;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Contract\Core\Routing\ResolverInterface',
    'Vasoft\Joke\Contract\Container\ResolverInterface',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Contract\Container\ResolverInterface instead
     */
    interface ResolverInterface extends NewResolverInterface {}
}
class_alias(NewResolverInterface::class, __NAMESPACE__ . '\ResolverInterface');
