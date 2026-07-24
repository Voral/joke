<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Provider\RateLimiterServiceProvider;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Contract\Storage\RateLimiterInterface;
use Vasoft\Joke\RateLimiter\ClientIdentifierInterface;
use Vasoft\Joke\RateLimiter\DefaultClientIdentifier;
use Vasoft\Joke\Storage\ArrayStorage;

/**
 * Тесты для RateLimiterServiceProvider.
 */
class RateLimiterServiceProviderTest extends TestCase
{
    private ServiceContainer $container;
    private RateLimiterServiceProvider $provider;

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
        $this->provider = new RateLimiterServiceProvider();
        $this->provider->setStoragePath(sys_get_temp_dir() . '/joke_test_storage');
    }

    protected function tearDown(): void
    {
        // Очистка
        $dir = sys_get_temp_dir() . '/joke_test_storage';
        if (is_dir($dir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($dir);
        }
    }

    public function testProvides(): void
    {
        $provides = $this->provider->provides();
        
        $this->assertContains(StorageInterface::class, $provides);
        $this->assertContains(RateLimiterInterface::class, $provides);
        $this->assertContains(ClientIdentifierInterface::class, $provides);
    }

    public function testRegister(): void
    {
        $this->provider->register($this->container);
        
        $this->assertTrue($this->container->has(StorageInterface::class));
        $this->assertTrue($this->container->has(RateLimiterInterface::class));
        $this->assertTrue($this->container->has(ClientIdentifierInterface::class));
    }

    public function testProvidesConfig(): void
    {
        $configs = RateLimiterServiceProvider::provideConfigs();
        
        $this->assertContains('Vasoft\\Joke\\Config\\RateLimitConfig', $configs);
    }
}
