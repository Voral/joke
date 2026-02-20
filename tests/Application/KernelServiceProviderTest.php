<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Application;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Application\KernelServiceProvider;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Application\KernelServiceProvider
 */
final class KernelServiceProviderTest extends TestCase
{
    public function testUnknownConfig(): void
    {
        $this->expectException(UnknownConfigException::class);
        $this->expectExceptionMessage('Unknown config class: unknown');
        KernelServiceProvider::buildConfig('unknown', new ServiceContainer());
    }
}
