<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Service;

use Vasoft\Joke\Container\ParameterResolver;
use Vasoft\Joke\Container\ServiceContainer;

class TestableParameterResolver extends ParameterResolver
{
    public static int $constructorCallCount = 0;

    public function __construct(ServiceContainer $serviceContainer)
    {
        parent::__construct($serviceContainer);
        ++self::$constructorCallCount;
    }
}
