<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Provider;

use Vasoft\Joke\Provider\AbstractProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Provider\AbstractProvider
 */
final class AbstractProviderTest extends TestCase
{
    public function testDefaultRequires(): void
    {
        /** @var AbstractProvider $provider */
        $provider = new class extends AbstractProvider {
            public function register(): void
            {
                // empty
            }

            public function boot(): void
            {
                // empty
            }

            public function provides(): array
            {
                return [];
            }
        };
        self::assertSame([], $provider->requires());
    }
}
