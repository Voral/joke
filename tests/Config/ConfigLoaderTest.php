<?php

namespace Vasoft\Joke\Tests\Config;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Config\ConfigLoader;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Kernel\Exceptions\KernelException;

class ConfigLoaderTest extends TestCase
{
    private static string $basePath = '';
    private static array $dirForClean = [];

    public static function setUpBeforeClass(): void
    {
        $name = random_int(1, 100) . '-config';
        self::$basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
        mkdir(self::$basePath);
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanDir(self::$basePath);
    }

    private static function cleanDir(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = scandir($dir);
        if (!is_array($files)) {
            return;
        }
        $items = array_diff($files, ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::cleanDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function tearDown(): void
    {
        foreach (self::$dirForClean as $dir) {
            self::cleanDir($dir);
        }
        self::$dirForClean = [];
    }

    protected function writeConfigFile(string $subDir, string $name, array $content): void
    {
        $path = self::$basePath . $subDir . DIRECTORY_SEPARATOR;
        if (!file_exists($path)) {
            mkdir($path, recursive: true);
        }
        self::$dirForClean[] = $path;
        $fileName = $path . $name . '.php';
        file_put_contents($fileName, '<?php return ' . var_export($content, true) . ';');
    }

    public function testLoading(): void
    {
        $firstConfig = ['first-var' => 1];
        $secondConfig = ['first-var' => 2];
        $this->writeConfigFile('config', 'first', $firstConfig);
        $this->writeConfigFile('config', 'second', $secondConfig);
        $loader = new ConfigLoader(self::$basePath . DIRECTORY_SEPARATOR . 'config');
        $vars = $loader->load();
        self::assertSame($firstConfig, $vars['first']);
        self::assertSame($secondConfig, $vars['second']);
    }

    public function testLoadingOneLevelOnly(): void
    {
        $firstConfig = ['first-var' => 1];
        $secondConfig = ['first-var' => 2];
        $this->writeConfigFile('config', 'first', $firstConfig);
        $this->writeConfigFile('config/lazy', 'second', $secondConfig);
        $loader = new ConfigLoader(self::$basePath . 'config');
        $vars = $loader->load();
        self::assertSame($firstConfig, $vars['first']);
        self::assertArrayNotHasKey('second', $vars);
        self::assertArrayHasKey('first', $vars);
    }

    public function testLazySuccess(): void
    {
        $firstConfig = ['some-var' => 1];
        $this->writeConfigFile('config/lazy', 'first', $firstConfig);
        $loader = new ConfigLoader(
            self::$basePath . 'config',
            [self::$basePath . 'config/lazy'],
        );
        $vars = $loader->loadLazy('first');
        self::assertArrayHasKey('some-var', $vars);
    }

    public function testNoFile(): void
    {
        self::$dirForClean[] = $path = self::$basePath . 'config/lazy';
        mkdir($path, recursive: true);

        $loader = new ConfigLoader(self::$basePath . DIRECTORY_SEPARATOR . 'config', [self::$basePath . 'config/lazy']);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('No configuration for core');
        $loader->loadLazy('core');
    }

    public function testNotAbsoluteBasePath(): void
    {
        $loader = new ConfigLoader('config');
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Path must be absolute: config/');
        $loader->load();
    }

    public function testNotAbsoluteLazy(): void
    {
        $loader = new ConfigLoader(self::$basePath . 'config', ['config/lazy']);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Path must be absolute: config/lazy/');
        $loader->loadLazy('test');
    }

    public function testBasePathNotExists(): void
    {
        $path = self::$basePath . 'config';
        $loader = new ConfigLoader($path, ['config/lazy']);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Base config path does not exist: ' . $path . DIRECTORY_SEPARATOR);
        $loader->load();
    }
    public function testLazyPathNotExists(): void
    {
        $loader = new ConfigLoader(self::$basePath . 'config', [self::$basePath.'config/lazy']);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Lazy config path does not exist: ' . self::$basePath.'config/lazy' . DIRECTORY_SEPARATOR);
        $loader->loadLazy('test');
    }
    public function testLazyPathNotExistsForWindows(): void
    {
        $loader = new ConfigLoader(self::$basePath . 'config', ['C:\config\lazy']);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Lazy config path does not exist: C:\config\lazy' . DIRECTORY_SEPARATOR);
        $loader->loadLazy('test');
    }
}
