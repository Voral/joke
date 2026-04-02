<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Response\Html\Asset\AssetFileManager;
use Vasoft\Joke\Http\Response\Html\AttributeCollection;
use Vasoft\Joke\Http\Response\Html\PageBuilder;
use Vasoft\Joke\Http\Response\Html\PageBuilderConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\PageBuilder
 */
final class PageBuilderTest extends TestCase
{
    private static PageBuilderConfig $config;
    private static AssetFileManager $manager;

    public static function setUpBeforeClass(): void
    {
        self::$config = new PageBuilderConfig();
        self::$manager = new AssetFileManager(
            __DIR__,
            __DIR__ . '/public',
            '/assets/',
        );
    }

    public function testEmptyPage(): void
    {
        self::$config->setTagSeparator('');
        $builder = new PageBuilder(self::$config, self::$manager);
        self::assertSame('<html lang="ru"><head><meta charset="UTF-8"></head><body></body></html>', $builder->build());
    }

    public function testMeta(): void
    {
        self::$config->setTagSeparator('');
        $builder = new PageBuilder(self::$config, self::$manager);
        $builder->addMeta('test', 'v1');
        self::assertSame(
            '<html lang="ru"><head><meta charset="UTF-8"><meta name="test" content="v1"></head><body></body></html>',
            $builder->build(),
        );
    }

    public function testTitle(): void
    {
        self::$config->setTagSeparator('');
        $builder = new PageBuilder(self::$config, self::$manager);
        $builder->setTitle('test');
        self::assertSame(
            '<html lang="ru"><head><title>test</title><meta charset="UTF-8"></head><body></body></html>',
            $builder->build(),
        );
    }

    public function testContent(): void
    {
        self::$config->setTagSeparator('');
        $builder = new PageBuilder(self::$config, self::$manager);
        $builder->setContent('<h1>test</h1><p>Hello</p>');
        self::assertSame(
            '<html lang="ru"><head><meta charset="UTF-8"></head><body><h1>test</h1><p>Hello</p></body></html>',
            $builder->build(),
        );
    }

    public function testCharset(): void
    {
        self::$config->setTagSeparator('');
        $builder = new PageBuilder(self::$config, self::$manager);
        $builder->setCharset('windows-1251');
        self::assertSame(
            '<html lang="ru"><head><meta charset="windows-1251"></head><body></body></html>',
            $builder->build(),
        );
    }

    public function testHtmlAttributes(): void
    {
        self::$config->setTagSeparator('');
        $builder = new PageBuilder(self::$config, self::$manager);
        $builder->htmlAttributes->set('lang', 'fr')->append('class', 'mobile');
        self::assertSame(
            '<html lang="fr" class="mobile"><head><meta charset="UTF-8"></head><body></body></html>',
            $builder->build(),
        );
    }

    public function testBodyAttributes(): void
    {
        self::$config->setTagSeparator('');
        $builder = new PageBuilder(self::$config, self::$manager);
        $builder->bodyAttributes->append('class', 'mobile');
        self::assertSame(
            '<html lang="ru"><head><meta charset="UTF-8"></head><body class="mobile"></body></html>',
            $builder->build(),
        );
    }

    public function testScripts(): void
    {
        self::$config->setTagSeparator("\n");
        $builder = new PageBuilder(self::$config, self::$manager);
        $attributes = new AttributeCollection()->flag('integrity', true);
        $builder->js->addToBody('https://site.ru/s1.js', 50, $attributes);
        $builder->js->addToBody('https://site.ru/s2.js', 1);
        $builder->js->addToBody('https://site.ru/s3.js');
        $builder->js->addToHead('https://site.ru/s3.js');
        $builder->js->addToHead('https://site.ru/s4.js');
        $builder->js->addToBody('https://site.ru/s4.js');
        self::assertSame(
            <<<'HTML'
                <html lang="ru">
                <head>
                <meta charset="UTF-8">
                <script src="https://site.ru/s3.js"/>
                <script src="https://site.ru/s4.js"/>
                </head>
                <body>
                <script src="https://site.ru/s2.js"/>
                <script integrity src="https://site.ru/s1.js"/>
                </body>
                </html>
                HTML,
            $builder->build(),
        );
    }
}
