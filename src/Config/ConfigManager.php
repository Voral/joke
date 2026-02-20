<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config;

use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Config\Exceptions\WrongConfigException;
use Vasoft\Joke\Config\Exceptions\WrongConfigFileException;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\Contract\Provider\ServiceProviderInterface;
use Vasoft\Joke\Support\Normalizers\Path;

/**
 * Менеджер конфигураций приложения.
 *
 * Отвечает за управление жизненным циклом конфигурационных объектов:
 * - Загрузка базовых конфигураций при старте приложения (Eager loading).
 * - Ленивая загрузка конфигураций по требованию (Lazy loading).
 * - Регистрация конфигураций в DI-контейнере в качестве синглтонов или фабрик.
 *
 * Конфигурационные файлы могут возвращать:
 * 1. Экземпляр класса, наследующего {@see AbstractConfig}.
 * 2. Ассоциативный массив, где ключ — полное имя класса (class-string),
 *    а значение — экземпляр {@see AbstractConfig} или замыкание (Closure), возвращающее его.
 *
 * В области видимости конфигурационных файлов доступна переменная $env типа {@see Environment}
 * для безопасного доступа к переменным окружения.
 */
class ConfigManager
{
    /**
     * Нормализованный путь к директории с ленивыми конфигурациями.
     */
    private readonly string $lazyPath;
    /** Флаг существования директории с ленивыми конфигурациями. */
    private readonly bool $lazyPathExists;
    /**
     * Нормализованный путь к директории с базовыми конфигурациями.
     */
    private readonly string $basePath;
    /**
     * Экземпляр окружения для передачи в конфигурационные файлы.
     */
    private readonly Environment $env;
    /**
     * Сервис для нормализации путей к файлам.
     */
    private readonly Path $pathNormalizer;

    /**
     * Список конфигураций и предоставляющих их предостайдеров
     * - ключ - класс конфигурации
     * - значение - класс провайдера.
     *
     * @var array<class-string<AbstractConfig>,class-string<ConfigurableServiceProviderInterface>>
     */
    private array $configProviders = [];

    /**
     * Конструктор менеджера конфигураций.
     *
     * @param ServiceContainer $serviceContainer контейнер внедрения зависимостей
     * @param string           $basePath         нормализованный путь к директории с базовыми конфигурациями
     * @param string           $lazyPath         нормализованный путь к директории с ленивыми конфигурациями
     *
     * @throws ParameterResolveException если зависимости (Environment, Path) не найдены в контейнере
     */
    public function __construct(
        public readonly ServiceContainer $serviceContainer,
        string $basePath,
        string $lazyPath,
    ) {
        $this->env = $this->serviceContainer->get(Environment::class);
        $this->pathNormalizer = $this->serviceContainer->get(Path::class);
        $this->basePath = $this->pathNormalizer->normalizeDir($basePath);
        $this->lazyPath = $this->pathNormalizer->normalizeDir($lazyPath);
        $this->lazyPathExists = !empty($this->lazyPath) && is_dir($this->lazyPath);
    }

    /**
     * Загружает все конфигурационные файлы из базовой директории в DI-контейнер.
     *
     * @throws WrongConfigFileException если файл конфигурации возвращает некорректное значение
     */
    public function load(): void
    {
        if (is_dir($this->basePath)) {
            $this->loadFromPath($this->basePath);
        }
    }

    /**
     * Загружает PHP-файлы конфигурации из указанной директории.
     *
     * @param string $path нормализованный путь к директории
     *
     * @throws WrongConfigFileException если файл возвращает значение недопустимого типа
     */
    protected function loadFromPath(string $path): void
    {
        $iterator = new \DirectoryIterator($path);
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $this->loadFile($file->getRealPath());
            }
        }
    }

    /**
     * Загружает и регистрирует один конфигурационный файл.
     *
     * Файл должен возвращать либо объект {@see AbstractConfig}, либо ассоциативный массив
     * вида `[class-string => AbstractConfig|Closure]`.
     *
     * @param string $path абсолютный путь к PHP-файлу конфигурации
     *
     * @throws WrongConfigFileException если файл возвращает значение недопустимого типа
     */
    protected function loadFile(string $path): void
    {
        $env = $this->env;
        /** @phpstan-ignore-next-line */
        $config = static function () use ($env, $path) {
            return require $path;
        };
        $result = $config();
        if ($result instanceof AbstractConfig) {
            $result->freeze();
            $this->serviceContainer->registerSingleton($result::class, $result);

            return;
        }
        if (is_array($result)) {
            $this->registerFromArray($result);

            return;
        }

        throw new WrongConfigFileException(str_replace($this->pathNormalizer->basePath, '', $path));
    }

    /**
     * Регистрирует конфигурации из ассоциативного массива.
     *
     * @param array<string, AbstractConfig|\Closure> $configs ассоциативный массив конфигураций
     */
    protected function registerFromArray(array $configs): void
    {
        foreach ($configs as $configName => $config) {
            if ($config instanceof AbstractConfig) {
                $config->freeze();
                $this->serviceContainer->registerSingleton($configName, $config);

                continue;
            }
            if ($config instanceof \Closure) {
                $factory = static fn() => ConfigManager::instance($config, $configName);
                $this->serviceContainer->registerSingleton($configName, $factory);
            }
        }
    }

    /**
     * Получает экземпляр конфигурации по имени класса.
     *
     * Если конфигурация уже зарегистрирована в контейнере, возвращает её.
     * В противном случае пытается загрузить лениво через {@see loadLazy()}.
     *
     * @param class-string $configClass полное имя класса конфигурации
     *
     * @return AbstractConfig экземпляр конфигурации
     *
     * @throws ConfigException           файл не найден или файл не содержит определения для запрошенного класса
     * @throws ParameterResolveException При ошибке резолвера
     * @throws WrongConfigFileException  если файл возвращает значение недопустимого типа
     */
    public function get(string $configClass): AbstractConfig
    {
        if ($this->serviceContainer->has($configClass)) {
            return $this->serviceContainer->get($configClass);
        }
        $entity = $this->loadLazy($configClass);
        if (null !== $entity) {
            return $entity;
        }
        $config = $this->resolveDefault($configClass);
        $config->freeze();

        return $config;
    }

    private function resolveDefault(string $configClass): AbstractConfig
    {
        if (array_key_exists($configClass, $this->configProviders)) {
            $config = ($this->configProviders[$configClass])::buildConfig($configClass, $this->serviceContainer);
            if (!$config instanceof $configClass) {
                throw new WrongConfigException(
                    "Provider {$this->configProviders[$configClass]} returned invalid type for {$configClass}",
                );
            }
            $this->serviceContainer->registerSingleton($configClass, $config);
            unset($this->configProviders[$configClass]);

            return $config;
        }

        throw new ConfigException('Unknown config class: ' . $configClass);
    }

    /**
     * Выполняет ленивую загрузку конфигурации из файлов.
     *
     * Ищет файл `{ShortClassName}.php` в директориях ленивых конфигураций
     * При нахождении файла загружает его содержимое и регистрирует все найденные конфигурации.
     *
     * @param string $name полное имя класса конфигурации
     *
     * @return ?AbstractConfig загруженный экземпляр конфигурации
     *
     * @throws ParameterResolveException При ошибке резолвера
     * @throws WrongConfigFileException  если файл возвращает значение недопустимого типа
     */
    private function loadLazy(string $name): ?AbstractConfig
    {
        if ($this->lazyPathExists) {
            $parts = explode('\\', $name);
            $configName = end($parts);

            $fileName = $this->lazyPath . $configName . '.php';
            if (file_exists($fileName)) {
                $this->loadFile($fileName);

                return $this->serviceContainer->get($name);
            }
        }

        return null;
    }

    /**
     * Вспомогательный метод для безопасного создания экземпляра из замыкания.
     *
     * @param \Closure $closure    замыкание-фабрика
     * @param string   $configName имя класса конфигурации (для сообщения об ошибке)
     *
     * @return AbstractConfig созданный экземпляр
     *
     * @throws WrongConfigException если замыкание вернуло объект неверного типа
     */
    public static function instance(\Closure $closure, string $configName): AbstractConfig
    {
        $config = $closure();
        if (!$config instanceof AbstractConfig) {
            throw new WrongConfigException($configName);
        }
        $config->freeze();

        return $config;
    }

    /**
     * Регистрирует провайдеры конфигураций и сопоставляет их с предоставляемыми классами конфигов.
     *
     * Метод перебирает переданные классы провайдеров, вызывает их статический метод provideConfigs()
     * и строит внутреннюю карту соответствия [ConfigClass => ProviderClass].
     * Провайдеры не реализующие {@see ConfigurableServiceProviderInterface} пропускаются
     *
     * @param list<class-string<ServiceProviderInterface>> $providers
     */
    public function registerProviders(array $providers): self
    {
        foreach ($providers as $provider) {
            if (is_subclass_of($provider, ConfigurableServiceProviderInterface::class)) {
                $configs = $provider::provideConfigs();
                foreach ($configs as $config) {
                    $this->configProviders[$config] = $provider;
                }
            }
        }

        return $this;
    }
}
