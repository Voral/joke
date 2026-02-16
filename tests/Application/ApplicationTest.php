<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Application;

use Vasoft\Joke\Config\EnvironmentLoader;
use Vasoft\Joke\Logging\Logger;
use Vasoft\Joke\Logging\LogLevel;
use Vasoft\Joke\Tests\Fixtures\Logger\FakeLogger;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Vasoft\Joke\Application\KernelConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Contract\Routing\RouterInterface;
use Vasoft\Joke\Application\Application;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Support\Normalizers\Path;
use Vasoft\Joke\Tests\Fixtures\Middlewares\SingleMiddleware;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Application\Application
 */
final class ApplicationTest extends TestCase
{
    public static string $basePath = '';
    public static string $bootstrapPath = '';

    public static function setUpBeforeClass(): void
    {
        $name = 'Config' . random_int(1, 100);
        $base = dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Fixtures' . \DIRECTORY_SEPARATOR;
        self::$basePath = $base . $name . \DIRECTORY_SEPARATOR;
        self::$bootstrapPath = self::$basePath . 'bootstrap' . \DIRECTORY_SEPARATOR;
        mkdir(self::$bootstrapPath, recursive: true);
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

    protected function writeKernelBootstrap(string $content): void
    {
        file_put_contents(self::$bootstrapPath . 'kernel.php', '<?php return ' . $content);
    }

    public function testLoadingEnvironment(): void
    {
        $di = new ServiceContainer();
        new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            $di,
        );
        $byAlias = $di->get('env');
        $byClass = $di->get(Environment::class);
        self::assertInstanceOf(Environment::class, $byAlias);
        self::assertSame($byAlias, $byClass);
    }

    public function testExecuteDefaultHtml(): void
    {
        $di = new ServiceContainer();
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            $di,
        );
        ob_start();
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $app->handle($request);
        $output = ob_get_clean();
        self::assertStringContainsString('<li><a href="/name/Alex">Hi Alex</a>', $output);
        self::assertSame($request, $di->get(HttpRequest::class));
    }

    public function testExecuteDefaultJson(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            new ServiceContainer(),
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/json/Alex']));
        $output = ob_get_clean();
        self::assertSame('{"fio":"Alex"}', $output);
    }

    public function testDefaultMiddleware(): void
    {
        $app = new Application(
            dirname(__DIR__) . \DIRECTORY_SEPARATOR . '/Fixtures/no-wildcard',
            '/tests/Fixtures/no-wildcard/routes/web.php',
            new ServiceContainer(),
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/not-found-url']));
        $output = ob_get_clean();
        self::assertSame('{"message":"Route not found"}', $output);
    }

    public function testWildCard(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            new ServiceContainer(),
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/not-found-url']));
        $output = ob_get_clean();
        self::assertSame('Запрошен несуществующий путь: not-found-url', $output);
    }

    #[RunInSeparateProcess]
    public function testDefaultRouteMiddleware(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            new ServiceContainer(),
        );
        self::assertSame(PHP_SESSION_NONE, session_status());
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/Alex']));
        $output = ob_get_clean();
        self::assertSame(PHP_SESSION_ACTIVE, session_status(), 'Session middleware is not active');
        self::assertSame('Hi Alex', $output);
    }

    public function testAddMiddleware(): void
    {
        $middleware = new SingleMiddleware();
        $middleware->index = 3;
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            new ServiceContainer(),
        )
            ->addMiddleware(SingleMiddleware::class)
            ->addMiddleware($middleware);
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame('Middleware 0 begin#Middleware 3 begin#Hi jons#Middleware 3 end#Middleware 0 end', $output);
    }

    public function testAddMiddlewareAndRouteMiddleware(): void
    {
        $middleware = new SingleMiddleware();
        $middleware->index = 3;
        $routeMiddleware = new SingleMiddleware();
        $routeMiddleware->index = 4;
        $routeMiddleware2 = new SingleMiddleware();
        $routeMiddleware2->index = 5;

        $diContainer = new ServiceContainer();
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            $diContainer,
        )
            ->addMiddleware(SingleMiddleware::class)
            ->addMiddleware($middleware)
            ->addRouteMiddleware($routeMiddleware);
        /** @var Router $router */
        $router = $diContainer->get(RouterInterface::class);
        $route = $router->route('hiName');
        $route->addMiddleware($routeMiddleware2);

        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame(
            'Middleware 0 begin#Middleware 3 begin#Middleware 4 begin#Middleware 5 begin#Hi jons#Middleware 5 end#Middleware 4 end#Middleware 3 end#Middleware 0 end',
            $output,
        );
    }

    public function testAddMiddlewareAndRouteMiddlewareFilter(): void
    {
        $middleware = new SingleMiddleware();
        $middleware->index = 3;
        $routeMiddleware1 = new SingleMiddleware();
        $routeMiddleware1->index = 4;
        $routeMiddleware2 = new SingleMiddleware();
        $routeMiddleware2->index = 5;
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            new ServiceContainer(),
        )
            ->addMiddleware(SingleMiddleware::class)
            ->addMiddleware($middleware)
            ->addRouteMiddleware($routeMiddleware1)
            ->addRouteMiddleware($routeMiddleware2, groups: ['filtered']);

        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame(
            'Middleware 0 begin#Middleware 3 begin#Middleware 4 begin#Hi jons#Middleware 4 end#Middleware 3 end#Middleware 0 end',
            $output,
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name-filtered/jons']));
        $output = ob_get_clean();
        self::assertSame(
            'Middleware 0 begin#Middleware 3 begin#Middleware 4 begin#Middleware 5 begin#Hi jons#Middleware 5 end#Middleware 4 end#Middleware 3 end#Middleware 0 end',
            $output,
        );
    }

    public function testWrongMiddleware(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            'routes/web.php',
            new ServiceContainer(),
        )->addMiddleware(Router::class);
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/name/jons']));
        $output = ob_get_clean();
        self::assertSame(
            '{"message":"\'Middleware Vasoft\\\Joke\\\Routing\\\Router must implements MiddlewareInterface"}',
            $output,
        );
    }

    public function testKernelBootstrapWrong(): void
    {
        self::writeKernelBootstrap('new \Vasoft\Joke\Tests\Fixtures\Config\SingleConfig();');
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('kernel.php must return a KernelConfig instance.');
        new Application(self::$basePath, '', new ServiceContainer());
    }

    public function testKernelBootstrapSuccess(): void
    {
        self::writeKernelBootstrap('new \Vasoft\Joke\Application\KernelConfig()->setLazyConfigPath("custom_lazy");');
        $container = new ServiceContainer();
        new Application(self::$basePath, '', $container);
        self::assertSame('custom_lazy', $container->get(KernelConfig::class)->getLazyConfigPath());
    }

    #[RunInSeparateProcess]
    public function testExceptionOnBootstrap(): void
    {
        $pathNormalizer = new Path(__DIR__);
        $logger = new FakeLogger();
        $environment = new Environment(new EnvironmentLoader($pathNormalizer->basePath));

        $container = self::createStub(ServiceContainer::class);
        $container
            ->method('get')
            ->willReturnCallback(static function ($name) use ($environment, $pathNormalizer, $logger): mixed {
                return match ($name) {
                    Environment::class, 'env' => $environment,
                    Path::class, 'normalizer.path' => $pathNormalizer,
                    Logger::class, 'logger' => $logger,
                };
            });
        $container
            ->method('registerAlias')
            ->willReturnCallback(static function ($alias, $entity) use ($container): ServiceContainer {
                if ('config' === $alias) {
                    throw new \Exception('Test exception');
                }

                return $container;
            });

        new Application(self::$basePath, '', $container);
        $log = $logger->getRecords();

        self::assertCount(1, $log);
        self::assertArrayHasKey('level', $log[0]);
        self::assertArrayHasKey('message', $log[0]);
        self::assertSame(LogLevel::ERROR, $log[0]['level']);
        self::assertInstanceOf(\Exception::class, $log[0]['message']);
        self::assertSame('Test exception', $log[0]['message']->getMessage());
    }
}
