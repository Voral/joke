<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html\Asset;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Response\Html\Asset\AssetFileManager;
use Vasoft\Joke\Http\Response\Html\Asset\ScriptCollection;
use Vasoft\Joke\Http\Response\Html\AttributeCollection;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\Asset\AssetCollection
 */
#[RunTestsInSeparateProcesses]
final class ScriptCollectionTest extends TestCase
{
    private static string $projectPath = '';
    private static string $documentRoot = '';

    public static function setUpBeforeClass(): void
    {
        $name = 'ScriptCollection';
        $base = dirname(__DIR__, 4) . \DIRECTORY_SEPARATOR . 'Fixtures/cache' . \DIRECTORY_SEPARATOR;
        self::$projectPath = $base . $name . \DIRECTORY_SEPARATOR;
    }

    #[TestDox('Корректное форматирование тега')]
    public function testScriptFormat(): void
    {
        $expect = <<<'HTML'
            <script src="https://vik.devv/public/Some/Test/Strucure/script.js"></script>
            <script language="JavaScript" src="https://vik.devv/public/Some/Test/Strucure/script.js?example=1"></script>
            HTML;

        $attributes = new AttributeCollection(['language' => 'JavaScript']);
        $manager = new AssetFileManager(self::$projectPath, self::$documentRoot);
        $collection = new ScriptCollection($manager, '/assets/', "\n");
        $collection->addToHead('https://vik.devv/public/Some/Test/Strucure/script.js');
        $collection->addToHead('https://vik.devv/public/Some/Test/Strucure/script.js?example=1', $attributes);
        self::assertSame($expect, $collection->buildForHead());
    }
}
