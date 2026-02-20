<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config;

use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Support\Normalizers\Path;

/**
 * Загрузчик конфигурационных файлов.
 *
 * Отвечает за загрузку конфигураций из двух типов путей:
 * - базовые пути — конфигурации, загружаемые при старте приложения;
 * - ленивые пути — конфигурации, загружаемые по требованию.
 *
 * Каждый конфигурационный файл должен возвращать массив.
 * Имя конфигурации определяется по имени файла без расширения .php.
 *
 * В конфигурационных файлах доступна переменная $env типа Environment
 *  для безопасного доступа к переменным окружения.
 */
class ConfigLoader
{
    /**
     * Список путей к директориям с ленивыми (загружаемыми по требованию) конфигурациями.
     * Ключ: нормализованные пути (с завершающим DIRECTORY_SEPARATOR).
     * Значение: false — не проверен, true — прошёл валидацию.
     *
     * @var array<string, bool>
     */
    private array $lazyPaths = [];
    /**
     * Список путей к директориям с базовыми (загружаемыми сразу) конфигурациями.
     * Ключ: нормализованные пути (с завершающим DIRECTORY_SEPARATOR).
     * Значение: false — не проверен, true — прошёл валидацию.
     *
     * @var array<string, bool>
     */
    private array $basePaths = [];

    /**
     * Конструктор загрузчика конфигураций.
     *
     * Все пути необходимо передавать либо абсолютными, либо относительно корня проекта
     *
     * @param string        $basePath       путь к основной директории с базовыми конфигурациями
     * @param Environment   $env            Экземпляр окружения для доступа из конфигурационных файлов
     * @param Path          $pathNormalizer Нормализатор путей
     * @param array<string> $lazyPaths      массив путей к директориям с ленивыми конфигурациями
     */
    public function __construct(
        string $basePath,
        private readonly Environment $env,
        private readonly Path $pathNormalizer,
        array $lazyPaths = [],
    ) {
        $this->addBasePath($basePath);
        array_walk($lazyPaths, $this->addLazyPath(...));
    }

    /**
     * Добавляет путь к директории с базовыми конфигурациями.
     *
     * Базовые конфигурации загружаются сразу при вызове метода load().
     * Все пути необходимо передавать либо абсолютными, либо относительно корня проекта
     *
     * @param string $path Путь к директории с конфигурацией
     *
     * @return $this
     */
    public function addBasePath(string $path): static
    {
        $normalized = $this->pathNormalizer->normalizeDir($path);
        $this->basePaths[$normalized] = false;

        return $this;
    }

    /**
     * Добавляет путь к директории с ленивыми конфигурациями.
     *
     * Ленивые конфигурации загружаются только по запросу через метод loadLazy().
     * Все пути необходимо передавать либо абсолютными, либо относительно корня проекта
     *
     * @param string $path Путь к директории с конфигурацией
     *
     * @return $this
     */
    public function addLazyPath(string $path): static
    {
        $normalized = $this->pathNormalizer->normalizeDir($path);
        $this->lazyPaths[$normalized] = false;

        return $this;
    }

    /**
     * Загружает все конфигурации из зарегистрированных базовых путей.
     *
     * Каждый PHP-файл в указанных директориях должен возвращать массив.
     * Имя конфигурации берётся из имени файла без расширения .php.
     *
     * @return array<string, array<string,mixed>> Ассоциативный массив вида ['config_name' => [...]]
     *
     * @throws ConfigException если каталог относительный или не существует
     */
    public function load(): array
    {
        $result = [];
        foreach ($this->basePaths as $path => &$validated) {
            if (!is_dir($path)) {
                throw new ConfigException("Base config path does not exist: {$path}");
            }
            $this->loadFromPath($path, $result);
            $validated = true;
        }
        unset($validated);

        return $result;
    }

    /**
     * Загружает конфигурационные файлы из указанной директории и добавляет их в результат.
     *
     * @param string                              $path    нормализованный путь к директории (с завершающим DIRECTORY_SEPARATOR)
     * @param array<string, array<string, mixed>> &$result Ссылка на массив для накопления результатов
     */
    protected function loadFromPath(string $path, array &$result): void
    {
        $iterator = new \DirectoryIterator($path);
        foreach ($iterator as $file) {
            if (
                $file->isFile()
                && 'php' === $file->getExtension()
            ) {
                $result[$file->getBasename('.php')] = $this->loadFile($file->getRealPath());
            }
        }
    }

    /**
     * Загружает один конфигурационный файл.
     *
     * Файл должен возвращать массив. Если возвращено не массив — используется пустой массив.
     *
     * @param string $path абсолютный путь к PHP-файлу конфигурации
     *
     * @return array<string,mixed> данные конфигурации
     */
    protected function loadFile(string $path): array
    {
        $env = $this->env;
        /** @phpstan-ignore-next-line */
        $loader = static function () use ($env, $path) {
            return require $path;
        };
        $vars = $loader();

        return is_array($vars) ? $vars : [];
    }

    /**
     * Загружает ленивую конфигурацию по её имени.
     *
     * Ищет файл {name}.php в зарегистрированных ленивых путях в обратном порядке
     * (последний добавленный путь имеет наивысший приоритет).
     *
     * @param string $name Имя конфигурации (без расширения .php).
     *
     * @return array<string, mixed> загруженная конфигурация
     *
     * @throws ConfigException если файл конфигурации не найден ни в одном из ленивых путей или зарегистрирован не существующий путь
     */
    public function loadLazy(string $name): array
    {
        $dirs = array_reverse($this->lazyPaths);
        foreach ($dirs as $path => &$validated) {
            if (!$validated) {
                if (!is_dir($path)) {
                    throw new ConfigException("Lazy config path does not exist: {$path}");
                }
                $validated = true;
            }
            $fileName = $path . $name . '.php';
            if (file_exists($fileName)) {
                return $this->loadFile($fileName);
            }
        }
        unset($validated);

        throw new ConfigException('No configuration for ' . $name);
    }
}
