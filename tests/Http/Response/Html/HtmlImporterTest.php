<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Http\Response\Html\Asset\AssetFileManager;
use Vasoft\Joke\Http\Response\Html\HtmlImporter;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Response\Html\PageBuilder;
use Vasoft\Joke\Http\Response\Html\PageBuilderConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\HtmlImporter
 */
final class HtmlImporterTest extends TestCase
{
    #[DataProvider('provideDetectHtmlCases')]
    public function testDetectHtml(string $value, bool $expected): void
    {
        self::assertSame($expected, HtmlImporter::isFullHtmlDocument($value));
    }

    public static function provideDetectHtmlCases(): iterable
    {
        yield ['', false];
        yield ['<htmltest>test</htmltest>', false];
        yield ['<html>test</html>', true];
        yield ['<html lang="id">test</html>', true];
        yield ['<headtest>test</headtest>', false];
        yield ['<head >test</head>', true];
        yield ['<bodytest>test</bodytest>', false];
        yield ['<body class="test">test</body>', true];
        yield ['<!doctype><h1>test</h1>', true];
    }

    #[DataProvider('provideImportCases')]
    public function testImport(string $source, string $expected): void
    {
        $manager = new AssetFileManager(
            __DIR__,
            __DIR__ . '/public',
            '/assets/',
        );
        $builder = new PageBuilder(
            new PageBuilderConfig()->setTagSeparator(''),
            $manager,
        );
        HtmlImporter::import($builder, $source);

        self::assertSame($expected, $builder->build());
    }

    public static function provideImportCases(): iterable
    {
        yield 'Only body content' => [
            '<h1>Example</h1>',
            '<html lang="ru"><head><meta charset="UTF-8"></head><body><h1>Example</h1></body></html>',
        ];
        yield 'Legacy charset tag' => [
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-16"></head><body>test</body></html>',
            '<html lang="ru"><head><meta charset="UTF-16"></head><body>test</body></html>',
        ];
        yield 'Charset tag' => [
            '<html><head><meta charset="UTF-16"></head><body>test</body></html>',
            '<html lang="ru"><head><meta charset="UTF-16"></head><body>test</body></html>',
        ];
        yield 'Empty head string' => [
            "<html><head>\n\n\n\t\n     \n<meta charset=\"UTF-16\"></head><body>test</body></html>",
            '<html lang="ru"><head><meta charset="UTF-16"></head><body>test</body></html>',
        ];
        yield 'Html tag attributes' => [
            '<html lang="li"><body>test</body></html>',
            '<html lang="li"><head><meta charset="UTF-8"></head><body>test</body></html>',
        ];
        yield 'Body tag attributes' => [
            '<html lang="li"><body class="example">test</body></html>',
            '<html lang="li"><head><meta charset="UTF-8"></head><body class="example">test</body></html>',
        ];
        yield 'Title' => [
            '<html lang="li"><head><title>Hello</title></head><body class="example">test</body></html>',
            '<html lang="li"><head><title>Hello</title><meta charset="UTF-8"></head><body class="example">test</body></html>',
        ];
        yield 'Meta' => [
            '<html lang="li"><head><meta name="example" content="test"><meta custom="222" value="big"></head><body class="example">test</body></html>',
            '<html lang="li"><head><meta charset="UTF-8"><meta name="example" content="test"><meta custom="222" value="big"></head><body class="example">test</body></html>',
        ];
        yield 'Assets' => [
            '<html><head><link href="https://ya.ru/s.css"/><script src="https://ya.ru/s.js"/></head><body></body></html>',
            '<html lang="ru"><head><meta charset="UTF-8"><link href="https://ya.ru/s.css"><script src="https://ya.ru/s.js"></script></head><body></body></html>',
        ];
    }
}
