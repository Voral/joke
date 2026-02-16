<?php

declare(strict_types=1);

namespace Application;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Application\KernelConfig;
use Vasoft\Joke\Application\KernelServiceProvider;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Provider\ServiceProviderInterface;
use Vasoft\Joke\Routing\RouterServiceProvider;
use Vasoft\Joke\Tests\Fixtures\Logger\FakeLogger;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Application\KernelConfig
 */
final class KernelConfigTest extends TestCase
{
    #[DataProvider('provideProvidersCases')]
    public function testProviders(string $setName, string $addName, string $getName): void
    {
        $default = [
            KernelServiceProvider::class,
            RouterServiceProvider::class,
        ];
        $provider = self::createStub(ServiceProviderInterface::class);
        $all = $default;
        $all[] = $provider::class;
        $config = new KernelConfig();
        $config->{$setName}($default);
        self::assertSame($default, $config->{$getName}());
        $config->{$addName}($provider::class);
        self::assertSame($all, $config->{$getName}());
        $config->{$setName}($default);
        self::assertSame($default, $config->{$getName}());
    }

    public static function provideProvidersCases(): iterable
    {
        yield ['setProviders', 'addProvider', 'getProviders'];
        yield ['setDeferredProviders', 'addDeferredProvider', 'getDeferredProviders'];
    }

    #[DataProvider('provideSetAndGetCases')]
    public function testSetAndGet(string $setName, string $getName, mixed $value): void
    {
        $config = new KernelConfig();
        $config->{$setName}($value);
        self::assertSame($value, $config->{$getName}());
    }

    public static function provideSetAndGetCases(): iterable
    {
        yield ['setBaseConfigPath', 'getBaseConfigPath', 'configTest'];
        yield ['setLazyConfigPath', 'getLazyConfigPath', 'configTest'];
    }

    #[DataProvider('provideFrozenCases')]
    public function testFrozen(string $setter, mixed $value): void
    {
        $config = new KernelConfig();
        $config->freeze();
        self::expectException(ConfigException::class);
        $config->{$setter}($value);
    }

    public static function provideFrozenCases(): iterable
    {
        yield ['addDeferredProvider', 'Provider'];
        yield ['addProvider', 'Provider'];
        yield ['setDeferredProviders', ['Provider']];
        yield ['setProviders', ['Provider']];
        yield ['setLazyConfigPath', 'lazy'];
        yield ['setBaseConfigPath', 'config'];
        yield ['setLogger', new FakeLogger()];
    }

    public function testSetLogger(): void
    {
        $logger = new FakeLogger();
        $config = new KernelConfig();
        $config->setLogger($logger);
        $container = new ServiceContainer();
        $config->registerLogger($container);

        self::assertSame($logger, $container->get('logger'));
    }
}
