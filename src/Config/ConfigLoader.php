<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config;

use Vasoft\Joke\Config\Exceptions\ConfigException;

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
 * Класс оперирует во всех публичных методах абсолютными путями, попытка добавить относительный путь
 * вызовет исключение в момент обращения к этому пути
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
     * @param string        $basePath  абсолютный путь к основной директории с базовыми конфигурациями
     * @param Environment   $env       Экземпляр окружения для доступа из конфигурационных файлов
     * @param array<string> $lazyPaths массив абсолютных путей к директориям с ленивыми конфигурациями
     */
    public function __construct(
        string $basePath,
        private readonly Environment $env,
        array $lazyPaths = [],
    ) {
        $this->addBasePath($basePath);
        array_walk($lazyPaths, $this->addLazyPath(...));
    }

    /**
     * Добавляет путь к директории с базовыми конфигурациями.
     *
     * Базовые конфигурации загружаются сразу при вызове метода load().
     *
     * @param string $path Абсолютный путь к директории, добавление относительного вызовет ошибку в момент загрузки
     *
     * @return $this
     */
    public function addBasePath(string $path): static
    {
        $normalized = $this->normalizePath($path);
        $this->basePaths[$normalized] = false;

        return $this;
    }

    /**
     * Добавляет путь к директории с ленивыми конфигурациями.
     *
     * Ленивые конфигурации загружаются только по запросу через метод loadLazy().
     *
     * @param string $path Абсолютный путь к директории, добавление относительного вызовет ошибку в момент загрузки
     *
     * @return $this
     */
    public function addLazyPath(string $path): static
    {
        $normalized = $this->normalizePath($path);
        $this->lazyPaths[$normalized] = false;

        return $this;
    }

    /**
     * Нормализация добавляемого пути.
     *
     * Проверяет, что путь является абсолютным и возвращает нормализованное значение
     * (с завершающим DIRECTORY_SEPARATOR).
     *
     * @param string $path Путь к директории
     *
     * @return string Нормализованный путь
     */
    private function normalizePath(string $path): string
    {
        return rtrim($path, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
    }

    /**
     * Проверяет, является ли путь абсолютным и существует ли.
     *
     * @param string $path      путь
     * @param string $scopeName имя типа каталога Базовый (Base) или Ленивый (Lazy)
     *
     * @throws ConfigException Если путь относительный или каталог не существует
     */
    private function assertPath(string $path, string $scopeName): void
    {
        if (!str_starts_with($path, \DIRECTORY_SEPARATOR)
            && !preg_match('~^[A-Z]:~i', $path)) {
            throw new ConfigException("Path must be absolute: {$path}");
        }
        if (!is_dir($path)) {
            throw new ConfigException("{$scopeName} config path does not exist: {$path}");
        }
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
            $this->assertPath($path, 'Base');
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
                $this->assertPath($path, 'Lazy');
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
