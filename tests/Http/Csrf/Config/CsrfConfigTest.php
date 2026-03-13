<?php

declare(strict_types=1);
namespace Vasoft\Joke\Tests\Http\Csrf\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Http\Cookies\CookieConfig;
use Vasoft\Joke\Http\Csrf\CsrfConfig;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Csrf\CsrfTransportMode;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Csrf\CsrfConfig
 */
final class CsrfConfigTest extends TestCase
{
    #[DataProvider('provideSetAndGetCases')]
    public function testSetAndGet(string $setName, string $getName, mixed $value): void
    {
        $config = new CsrfConfig();
        $config->{$setName}($value);
        self::assertSame($value, $config->{$getName});
    }

    public static function provideSetAndGetCases(): iterable
    {
        yield ['setTransportMode', 'transportMode', CsrfTransportMode::COOKIE];
        yield ['setCookieConfig', 'cookieConfig', new CookieConfig()];
    }

    #[DataProvider('provideSetAndGetCases')]
    public function testFrozen(string $setName, string $getName, mixed $value): void
    {
        $config = new CsrfConfig();
        $config->freeze();
        self::expectException(ConfigException::class);
        $config->{$setName}($value);
    }
}
