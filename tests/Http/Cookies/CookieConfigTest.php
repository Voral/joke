<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Cookies;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Http\Cookies\CookieConfig;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Cookies\SameSiteOption;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Cookies\CookieConfig
 */
final class CookieConfigTest extends TestCase
{
    #[DataProvider('provideSetAndGetCases')]
    public function testSetAndGet(string $setName, string $getName, mixed $value): void
    {
        $config = new CookieConfig();
        $config->{$setName}($value);
        self::assertSame($value, $config->{$getName});
    }

    public static function provideSetAndGetCases(): iterable
    {
        yield ['setLifeTime', 'lifetime', 199];
        yield ['setPath', 'path', '/'];
        yield ['setDomain', 'domain', '.'];
        yield ['setSecure', 'secure', true];
        yield ['setHttpOnly', 'httpOnly', true];
        yield ['setSameSite', 'sameSite', SameSiteOption::None];
    }

    #[DataProvider('provideSetAndGetCases')]
    public function testFrozen(string $setName, string $getName, mixed $value): void
    {
        $config = new CookieConfig();
        $config->freeze();
        self::expectException(ConfigException::class);
        $config->{$setName}($value);
    }
}
