<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response\Html\Asset;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Vasoft\Joke\Http\Response\Html\Asset\AssetFileManager;
use Vasoft\Joke\Http\Response\Html\Asset\CssCollection;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\Response\Html\AttributeCollection;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\Html\Asset\CssCollection
 */
#[CoversClass(CssCollection::class)]
#[TestDox('CssCollection — Коллекция подключаемых файлов css')]
final class CssCollectionTest extends TestCase
{
    use PHPMock;

    private static string $projectPath = '';
    private static string $documentRoot = '';
    private static string $cssFile = '';
    private static string $jsFile = '';
    private static string $assetUri = 'assets';

    public static function setUpBeforeClass(): void
    {
        $name = 'AssetCollection' . random_int(1, 100);
        $base = sys_get_temp_dir() . '/css_collection_' . bin2hex(random_bytes(8)) . \DIRECTORY_SEPARATOR;
        self::$projectPath = $base . $name . \DIRECTORY_SEPARATOR;
        self::$documentRoot = self::$projectPath . 'www' . \DIRECTORY_SEPARATOR;
        mkdir(self::$documentRoot . self::$assetUri . \DIRECTORY_SEPARATOR, recursive: true);
        mkdir(self::$projectPath . 'modules' . \DIRECTORY_SEPARATOR, recursive: true);

        self::$cssFile = self::$projectPath . 'modules/outside.css';
        file_put_contents(self::$cssFile, 'b{color:blue;}');
    }

    protected function setUp(): void
    {
        self::getFunctionMock('Vasoft\Joke\Http\Response\Html\Asset', 'md5')
            ->expects(self::atLeastOnce())
            ->willReturn('path-hash');
        self::getFunctionMock('Vasoft\Joke\Http\Response\Html\Asset', 'filemtime')
            ->expects(self::atLeastOnce())
            ->willReturn(100);
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanDir(self::$projectPath);
    }

    private static function cleanDir(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = scandir($dir);
        if (is_array($files)) {
            $items = array_diff($files, ['.', '..']);
            foreach ($items as $item) {
                $path = $dir . \DIRECTORY_SEPARATOR . $item;
                if (is_dir($path)) {
                    self::cleanDir($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }

    #[TestDox('Добавляется обязательный атрибут')]
    public function testRequiredAttribute(): void
    {
        $expect = '<link rel="stylesheet" href="/assets/modules/path-hash_outside.css?v=100"/>';

        $manager = new AssetFileManager(self::$projectPath, self::$documentRoot);
        $collection = new CssCollection($manager, '/assets/');
        $collection->addToBody(self::$cssFile);
        self::assertSame($expect, $collection->buildForBody());
    }

    #[TestDox('Обязательный атрибут не дублируется')]
    public function testRequiredAttributeNonDouble(): void
    {
        $expect = '<link rel="stylesheet" media="screen" href="/assets/modules/path-hash_outside.css?v=100"/>';

        $manager = new AssetFileManager(self::$projectPath, self::$documentRoot);
        $collection = new CssCollection($manager, '/assets/');
        $collection->addToBody(
            self::$cssFile,
            attributes: new AttributeCollection(
                ['rel' => 'stylesheet', 'media' => 'screen'],
            ),
        );
        self::assertSame($expect, $collection->buildForBody());
    }
}
