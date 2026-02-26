<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Container;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Routing\RouteInterface;

/**
 * @internal
 *
 * @coversDefaultClass  \Vasoft\Joke\Container\ServiceContainer
 */
final class ServiceContainerTest extends TestCase
{
    public function testWrongRouter(): void
    {
        $container = new ServiceContainer();
        $container->setRouter(new \stdClass());

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Router service is not available or not of correct type.');

        $container->getRouter();
    }
}
