<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Config;

use Vasoft\Joke\Config\Config;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Config\ConfigLoader;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Config\EnvironmentLoader;
use Vasoft\Joke\Config\Exceptions\ConfigException;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Config\Config
 */
final class ConfigTest extends TestCase
{
    public function testGetBase(): void
    {
        $baseConfig = [
            'core' => [
                'ttl' => 1000,
                'user' => [
                    'name' => 'Alex Tester',
                    'age' => 23,
                ],
            ],
        ];

        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::once())
            ->method('load')
            ->willReturn($baseConfig);


        $config = new Config($loader);
        self::assertSame(1000, $config->get('core.ttl'));
        self::assertSame('Alex Tester', $config->get('core.user.name'));
        self::assertSame('defaultValue', $config->get('core.user.lastname', 'defaultValue'));
        self::assertSame($baseConfig['core'], $config->get('core'));
    }

    public function testGetEmptyKey(): void
    {
        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::never())->method('load');


        $config = new Config($loader);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Config key cannot be empty');

        $config->get('');
    }

    public function testGetLazy(): void
    {
        $lazyConfig = ['testVar' => 'example', 'secondVar' => true];

        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::once())
            ->method('load')
            ->willReturn([]);
        $loader->expects(self::once())
            ->method('loadLazy')
            ->with('extended')
            ->willReturn($lazyConfig);


        $config = new Config($loader);
        self::assertSame('example', $config->get('extended.testVar'));
        self::assertTrue($config->get('extended.secondVar'));
        self::assertSame($lazyConfig, $config->get('extended'));
    }

    public function testGetNoConfigFirstTime(): void
    {
        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::once())
            ->method('load')
            ->willReturn([]);
        $loader->expects(self::once())
            ->method('loadLazy')
            ->with('extended')
            ->willThrowException(new ConfigException('Test'));

        $config = new Config($loader);
        self::expectExceptionMessage('Test');
        $config->get('extended');
    }

    public function testGetNoConfigTwice(): void
    {
        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::once())
            ->method('load')
            ->willReturn([]);
        $loader->expects(self::once())
            ->method('loadLazy')
            ->with('extended')
            ->willThrowException(new ConfigException('Test'));

        $config = new Config($loader);

        try {
            $config->get('extended');
        } catch (ConfigException $e) {
        }
        self::expectExceptionMessage("Configuration 'extended' not found");
        $config->get('extended');
    }

    public function testGetOrFailSuccess(): void
    {
        $baseConfig = [
            'core' => [
                'ttl' => 1000,
                'user' => [
                    'name' => 'Alex Tester',
                    'age' => 23,
                ],
            ],
        ];

        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::once())
            ->method('load')
            ->willReturn($baseConfig);


        $config = new Config($loader);
        self::assertSame(1000, $config->getOrFail('core.ttl'));
        self::assertSame('Alex Tester', $config->getOrFail('core.user.name'));
        self::assertSame($baseConfig['core'], $config->getOrFail('core'));
    }

    public function testGetOrFailDefaultException(): void
    {
        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::once())
            ->method('load')
            ->willReturn([]);


        $config = new Config($loader);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage("Property 'core.ttl' does not exist");
        $config->getOrFail('core.ttl');
    }

    public function testAccessLoader(): void
    {
        $loader = new ConfigLoader('/test', new Environment(new EnvironmentLoader('test')));
        $config = new Config($loader);

        self::assertSame(spl_object_id($loader), spl_object_id($config->getLoader()));
    }

    public function testGetOrFailCustomException(): void
    {
        /** @var ConfigLoader $loader */
        $loader = self::getMockBuilder(ConfigLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader->expects(self::once())
            ->method('load')
            ->willReturn([]);


        $config = new Config($loader);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Custom message: core.ttl');
        $config->getOrFail(
            'core.ttl',
            static fn(string $key) => new ConfigException('Custom message: ' . $key),
        );
    }
}
