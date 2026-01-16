<?php

namespace Vasoft\Joke\Tests\Fixtures\Service;

use Vasoft\Joke\Core\Routing\ParameterResolver;
use Vasoft\Joke\Core\ServiceContainer;

class TestableParameterResolver extends ParameterResolver
{
    public static int $constructorCallCount = 0;

    public function __construct(ServiceContainer $serviceContainer)
    {
        parent::__construct($serviceContainer);
        ++self::$constructorCallCount;
    }
}