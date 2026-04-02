<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Http\Response\Html\PageBuilderConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\PageBuilderConfig
 */
final class PageBuilderConfigTest extends TestCase
{
    #[DataProvider('provideSetAndGetCases')]
    public function testSetAndGet(string $setName, string $getName, mixed $value): void
    {
        $config = new PageBuilderConfig();
        $config->{$setName}($value);
        self::assertSame($value, $config->{$getName});
    }

    public static function provideSetAndGetCases(): iterable
    {
        yield ['setAssetsPathJs', 'assetsPathJs', 'asset/js-files'];
        yield ['setAssetsPathCss', 'assetsPathCss', 'css-asset'];
        yield ['setTagSeparator', 'tagSeparator', '#'];
    }

    #[DataProvider('provideSetAndGetCases')]
    public function testFrozen(string $setName, string $getName, mixed $value): void
    {
        $config = new PageBuilderConfig();
        $config->freeze();
        self::expectException(ConfigException::class);
        $config->{$setName}($value);
    }
}
