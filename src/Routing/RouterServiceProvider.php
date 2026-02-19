<?php

declare(strict_types=1);

namespace Vasoft\Joke\Routing;

use Vasoft\Joke\Config\Config;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Support\Normalizers\Path;

class RouterServiceProvider extends AbstractProvider
{
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    public function register(): void
    {
        // Empty body
    }

    public function boot(): void
    {
        /** @var Config $config */
        $config = $this->serviceContainer->get('config');
        /** @var Path $pathNormalize */
        $pathNormalize = $this->serviceContainer->get('normalizer.path');
        $router = $this->serviceContainer->getRouter();
        $router->addAutoGroups([StdGroup::WEB->value]);
        $file = $pathNormalize->normalizeFile($config->get('app.fileRoutes', 'routes/web.php'));
        if (file_exists($file)) {
            require $file;
        }
        $router->cleanAutoGroups();
    }

    public function provides(): array
    {
        return [];
    }

    public function requires(): array
    {
        return ['config'];
    }
}
