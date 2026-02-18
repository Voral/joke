<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Builder;

class FakeProviderBuilder
{
    private string $basePath = '';
    private string $namespace = '';

    private function ensureDir(): void
    {
        do {
            $this->namespace = 'Fake' . random_int(1, 99999);
            $base = dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'cache' . \DIRECTORY_SEPARATOR;
            $this->basePath = $base . $this->namespace . \DIRECTORY_SEPARATOR;
        } while (file_exists($this->basePath));
        mkdir($this->basePath);
    }

    public function __construct()
    {
        $this->ensureDir();
        $this->createCounterClass();
    }

    public function clean(): void
    {
        if (!file_exists($this->basePath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($this->basePath);
    }

    public function createProviderClass(
        string $name,
        array $requires = [],
        array $provides = [],
        ?\Closure $registerLogic = null,
        ?\Closure $bootLogic = null,
    ): string {
        $interface = 'extends \Vasoft\Joke\Provider\AbstractProvider';

        $providesMethod = '';
        $providesArray = empty($provides) ? '[]' : "['" . implode("', '", $provides) . "']";
        $providesMethod = "public function provides(): array { return {$providesArray}; }";

        $requiresArray = empty($requires) ? '[]' : "['" . implode("', '", $requires) . "']";
        $registerBody = $registerLogic
            ? 'if ($this->callback) { ($this->callback)(); }'
            : '// empty';

        $bootBody = $bootLogic ? '($this->callback)()' : '// empty';

        $code = <<<PHP
            namespace Vasoft\\Joke\\Tests\\Unit\\Provider\\Fixtures\\{$this->namespace};

            class {$name} {$interface} {
                public \$callback;

                public function __construct(\$callback = null) { \$this->callback = \$callback; }

                public function requires(): array { return {$requiresArray}; }

                {$providesMethod}

                public function register(): void {
                    Counter::\$registerCalled[] = '{$name}';
                    {$registerBody}
                }

                public function boot(): void {
                    Counter::\$bootCalled[] = '{$name}';
                    {$bootBody}
                }
                public static function getRegistered(): array {
                    return Counter::\$registerCalled;
                }
                public static function getBooted(): array {
                    return Counter::\$bootCalled;
                }
            }
            PHP;

        $fileName = $this->basePath . $name . '.php';
        file_put_contents($fileName, "<?php \n" . $code);
        include $fileName;

        return "Vasoft\\Joke\\Tests\\Unit\\Provider\\Fixtures\\{$this->namespace}\\{$name}";
    }

    private function createCounterClass(): void
    {
        $code = <<<PHP
            namespace Vasoft\\Joke\\Tests\\Unit\\Provider\\Fixtures\\{$this->namespace};

            class Counter {
                public static \$registerCalled = [];
                public static \$bootCalled = [];
            }
            PHP;

        $fileName = $this->basePath . 'Counter.php';
        file_put_contents($fileName, "<?php \n" . $code);
        include $fileName;
    }

    public function __destruct()
    {
        $this->clean();
    }
}
