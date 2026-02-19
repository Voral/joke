<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Config;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Config\ConfigLoader;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Config\EnvironmentLoader;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Support\Normalizers\Path;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Config\ConfigLoader
 */
final class ConfigLoaderTest extends TestCase
{
    use PHPMock;
    private static string $basePath = '';
    private static array $dirForClean = [];
    private static ?Environment $env = null;

    public static function setUpBeforeClass(): void
    {
        $name = random_int(1, 100) . '-config';
        $base = dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR;
        self::$basePath = $base . $name . \DIRECTORY_SEPARATOR;
        mkdir(self::$basePath);
        self::$env = new Environment(new EnvironmentLoader($base));
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
            $path = $dir . \DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::cleanDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    protected function tearDown(): void
    {
        foreach (self::$dirForClean as $dir) {
            self::cleanDir($dir);
        }
        self::$dirForClean = [];
    }

    protected function writeConfigFile(string $subDir, string $name, array $content): void
    {
        $path = self::$basePath . $subDir . \DIRECTORY_SEPARATOR;
        if (!file_exists($path)) {
            mkdir($path, recursive: true);
        }
        self::$dirForClean[] = $path;
        $fileName = $path . $name . '.php';
        file_put_contents($fileName, '<?php return ' . var_export($content, true) . ';');
    }

    protected function writeConfigFileWithEnv(string $subDir, string $name, string $configContent): void
    {
        $path = self::$basePath . $subDir . \DIRECTORY_SEPARATOR;
        if (!file_exists($path)) {
            mkdir($path, recursive: true);
        }
        self::$dirForClean[] = $path;
        $fileName = $path . $name . '.php';
        file_put_contents($fileName, '<?php ' . $configContent);
    }

    public function testLoading(): void
    {
        $firstConfig = ['first-var' => 1];
        $secondConfig = ['first-var' => 2];
        $this->writeConfigFile('config', 'first', $firstConfig);
        $this->writeConfigFile('config', 'second', $secondConfig);
        $loader = new ConfigLoader('config', self::$env, new Path(self::$basePath));
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
        $loader = new ConfigLoader(self::$basePath . 'config', self::$env, new Path(self::$basePath));
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
            'config',
            self::$env,
            new Path(self::$basePath),
            [self::$basePath . 'config/lazy'],
        );
        $vars = $loader->loadLazy('first');
        self::assertArrayHasKey('some-var', $vars);
    }

    public function testNoFile(): void
    {
        self::$dirForClean[] = $path = self::$basePath . 'config/lazy';
        mkdir($path, recursive: true);

        $loader = new ConfigLoader(
            'config',
            self::$env,
            new Path(self::$basePath),
            [self::$basePath . 'config/lazy'],
        );
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('No configuration for core');
        $loader->loadLazy('core');
    }

     public function testBasePathNotExists(): void
    {
        $path = self::$basePath . 'config_salt';
        $loader = new ConfigLoader($path, self::$env, new Path(self::$basePath), ['config/lazy']);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Base config path does not exist: ' . $path . \DIRECTORY_SEPARATOR);
        $loader->load();
    }

    public function testLazyPathNotExists(): void
    {
        $loader = new ConfigLoader(
            self::$basePath . 'config',
            self::$env,
            new Path(self::$basePath),
            [self::$basePath . 'config/lazy'],
        );
        self::expectException(ConfigException::class);
        self::expectExceptionMessage(
            'Lazy config path does not exist: ' . self::$basePath . 'config/lazy' . \DIRECTORY_SEPARATOR,
        );
        $loader->loadLazy('test');
    }

    #[RunInSeparateProcess]
    public function testLazyPathNotExistsForWindows(): void
    {
        $isDirMock = $this->getFunctionMock('Vasoft\Joke\Support\Normalizers', 'is_dir');
        $isDirMock->expects(self::once())->willReturn(true);

        $substrMock = $this->getFunctionMock('Vasoft\Joke\Support\Normalizers', 'substr');
        $substrMock->expects(self::once())->willReturn('WIN');

        $loader = new ConfigLoader('config', self::$env, new Path('C:\config'), ['C:\config\lazy']);
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Lazy config path does not exist: C:\config\lazy' . \DIRECTORY_SEPARATOR);
        $loader->loadLazy('test');
    }

    public function testEnvironmentVariablesInConfig(): void
    {
        /**
         * @var Environment $env
         */
        $env = self::getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects(self::once())->method('get')
            ->with('DB_USERNAME', 'default_user')
            ->willReturn('test_user');

        $this->writeConfigFileWithEnv(
            'config',
            'database',
            <<<'PHP'
                return [
                    'username' => $env->get('DB_USERNAME', 'default_user'),
                ];
                PHP,
        );

        $loader = new ConfigLoader('config', $env, new Path(self::$basePath));

        $config = $loader->load();

        self::assertSame('test_user', $config['database']['username']);
    }
}
