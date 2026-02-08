<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core;

use Vasoft\Joke\Contract\Core\Routing\ResolverInterface;
use Vasoft\Joke\Core\Routing\ParameterResolver;
use Vasoft\Joke\Core\ServiceContainer;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Tests\Fixtures\Service\SingleService;
use Vasoft\Joke\Tests\Fixtures\Service\TestableParameterResolver;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\ServiceContainer
 */
final class ServiceContainerTest extends TestCase
{
    public function testDefaults(): void
    {
        $container = new ServiceContainer();
        $resolver = $container->get(ResolverInterface::class);
        self::assertInstanceOf(ParameterResolver::class, $resolver);
        $container = $container->get(ServiceContainer::class);
        self::assertInstanceOf(ServiceContainer::class, $container);
    }

    public function testGetParameterResolver(): void
    {
        TestableParameterResolver::$constructorCallCount = 0;
        $container = new ServiceContainer();
        $container->registerSingleton(ResolverInterface::class, TestableParameterResolver::class);
        $container->getParameterResolver();
        $container->getParameterResolver();
        self::assertSame(1, TestableParameterResolver::$constructorCallCount);
    }

    public function testRegisterSingletonInstance(): void
    {
        $container = new ServiceContainer();
        $resolver = new TestableParameterResolver($container);
        TestableParameterResolver::$constructorCallCount = 0;

        $container->registerSingleton(ResolverInterface::class, $resolver);
        $container->getParameterResolver();
        $container->getParameterResolver();
        self::assertSame(0, TestableParameterResolver::$constructorCallCount);
    }

    public function testRegisterInstance(): void
    {
        $container = new ServiceContainer();
        $resolver = new TestableParameterResolver($container);
        TestableParameterResolver::$constructorCallCount = 0;

        $container->register(ParameterResolver::class, $resolver);
        $resolver1 = $container->getParameterResolver();
        $resolver2 = $container->getParameterResolver();
        self::assertSame(0, TestableParameterResolver::$constructorCallCount);
        self::assertSame($resolver1, $resolver2);
    }

    public function testMultipleObject(): void
    {
        $container = new ServiceContainer();
        $container->register(SingleService::class, SingleService::class);
        $service1 = $container->get(SingleService::class);
        $service2 = $container->get(SingleService::class);
        self::assertNotSame($service1, $service2);
    }

    public function testGetNotRegistered(): void
    {
        $container = new ServiceContainer();
        $service1 = $container->get(SingleService::class);
        self::assertNull($service1);
    }

    public function testRegisteredCallback(): void
    {
        $callbackCount = 0;
        $container = new ServiceContainer();
        $container->register(SingleService::class, static function () use (&$callbackCount) {
            ++$callbackCount;

            return new SingleService();
        });
        $service1 = $container->get(SingleService::class);
        $service2 = $container->get(SingleService::class);
        self::assertSame(2, $callbackCount);
        self::assertNotSame($service1, $service2);
    }

    public function testRegisteredSingletonCallback(): void
    {
        $callbackCount = 0;
        $container = new ServiceContainer();
        $container->registerSingleton(SingleService::class, static function () use (&$callbackCount) {
            ++$callbackCount;

            return new SingleService();
        });
        $service1 = $container->get(SingleService::class);
        $service2 = $container->get(SingleService::class);
        self::assertSame(1, $callbackCount);
        self::assertSame($service1, $service2);
    }
}
