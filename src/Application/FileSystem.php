<?php

declare(strict_types=1);

namespace Vasoft\Joke\Application;

use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Exceptions\FileSystemException;

/**
 * Утилитарный класс для нормализации и валидации файловых путей.
 *
 * Гарантирует, что все операции с путями выполняются относительно корректного
 * абсолютного базового пути. Класс является неизменяемым (immutable).
 *
 * Особенности:
 * - Автоматическая проверка существования и абсолютности базового пути при создании.
 * - Кроссплатформенная поддержка (Windows/Linux/macOS) через определение стиля путей.
 * - Нормализация завершающих разделителей для директорий и файлов.
 */
class FileSystem
{
    /**
     * Абсолютный базовый путь к корневой директории.
     * Всегда заканчивается разделителем директории.
     */
    public readonly string $basePath;

    /**
     * Права доступа по умолчанию для создаваемых директорий.
     */
    private const int DEFAULT_DIR_PERMISSIONS = 0o775;

    /**
     * Путь к директории временных и служебных данных (var).
     *
     * Используется для хранения кэша, логов и других генерируемых файлов.
     *
     * @property string $varPath
     */
    public string $varPath {
        get => $this->basePath . 'var' . \DIRECTORY_SEPARATOR;
    }
    /**
     * Путь к директории bootstrap.
     *
     * Содержит скрипты инициализации приложения (например, kernel.php).
     *
     * @property string $bootstrapPath
     */
    public string $bootstrapPath {
        get => $this->basePath . 'bootstrap' . \DIRECTORY_SEPARATOR;
    }
    /**
     * Путь к директории кэша.
     *
     * Используется для хранения скомпилированных шаблонов, кэша конфигурации
     * и других временных данных для ускорения работы приложения.
     *
     * @property string $cachePath
     */
    public string $cachePath {
        get => $this->varPath . 'cache' . \DIRECTORY_SEPARATOR;
    }
    /**
     * Путь к директории логов.
     *
     * Предназначена для хранения файлов журналов событий и ошибок приложения.
     *
     * @property string $logPath
     */
    public string $logPath {
        get => $this->varPath . 'log' . \DIRECTORY_SEPARATOR;
    }
    /**
     * Путь к публичной директории.
     *
     * Точка входа для веб-сервера. Содержит фронт-контроллер (index.php),
     * а также публичные ресурсы: CSS, JavaScript, изображения и другие файлы,
     * доступные напрямую из браузера.
     *
     * @property string $publicPath
     */
    public string $publicPath {
        get => $this->basePath . 'public' . \DIRECTORY_SEPARATOR;
    }
    private bool $isWindows;

    /**
     * Создает экземпляр нормализатора путей.
     *
     * @param string $basePath базовый путь к директории
     *
     * @throws ConfigException если путь не является абсолютным или не существует на диске
     */
    public function __construct(string $basePath)
    {
        $realBase = realpath($basePath);
        if (false === $realBase || !is_dir($realBase)) {
            throw new ConfigException("Path must be an existing directory: '{$basePath}'.");
        }
        $this->isWindows = 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
        if (!is_dir($realBase)) {
            throw new ConfigException("Path must be a directory: '{$basePath}'.");
        }
        $this->basePath = rtrim($realBase, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
    }

    /**
     * Нормализует путь к директории.
     *
     * Если передан относительный путь, он преобразуется в абсолютный путем добавления basePath.
     * Гарантирует, что результирующий путь всегда заканчивается ровно одним разделителем директории.
     *
     * @param string $path путь к директории (относительный или абсолютный)
     *
     * @return string абсолютный нормализованный путь к директории с завершающим разделителем
     */
    public function normalizeDir(string $path): string
    {
        if (!self::isAbsolute($path)) {
            $path = $this->basePath . $path;
        }

        return rtrim($path, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
    }

    /**
     * Нормализует путь к файлу.
     *
     * Если передан относительный путь, он преобразуется в абсолютный путем добавления basePath.
     * В отличие от директорий, путь к файлу не гарантированно должен заканчиваться разделителем.
     *
     * @param string $path путь к файлу (относительный или абсолютный)
     *
     * @return string абсолютный нормализованный путь к файлу
     */
    public function normalizeFile(string $path): string
    {
        if (self::isAbsolute($path)) {
            return $path;
        }

        return $this->basePath . $path;
    }

    /**
     * Проверяет, является ли путь абсолютным.
     *
     * Учитывает особенности операционной системы:
     * - Для Windows: проверяет наличие буквы диска с разделителем (например, "C:\" или "C:/").
     * - Для Unix-систем: проверяет наличие начального слэша ("/").
     *
     * @param string $path путь для проверки
     *
     * @return bool true, если путь абсолютный, иначе false
     */
    public function isAbsolute(string $path): bool
    {
        if ($this->isWindows) {
            return (bool) preg_match('~^[A-Z]:(\\\|/)~i', $path);
        }

        return str_starts_with($path, \DIRECTORY_SEPARATOR);
    }

    public function atBase(string $path): string
    {
        return $this->basePath . ltrim($path, \DIRECTORY_SEPARATOR);
    }

    public function atCache(string $path): string
    {
        return $this->cachePath . ltrim($path, \DIRECTORY_SEPARATOR);
    }

    public function atBootstrap(string $path): string
    {
        return $this->bootstrapPath . ltrim($path, \DIRECTORY_SEPARATOR);
    }

    public function atLog(string $path): string
    {
        return $this->logPath . ltrim($path, \DIRECTORY_SEPARATOR);
    }

    public function atVar(string $path): string
    {
        return $this->varPath . ltrim($path, \DIRECTORY_SEPARATOR);
    }

    public function at(string $directory, string $path): string
    {
        return $this->normalizeDir($directory) . ltrim($path, \DIRECTORY_SEPARATOR);
    }

    public function ensureDirectory(string $directory, int $permissions = self::DEFAULT_DIR_PERMISSIONS): void
    {
        $this->validatePath($directory);
        if (!is_dir($directory) && !mkdir($directory, $permissions, true) && !is_dir($directory)) {
            throw new FileSystemException("Unable to create directory: '{$directory}'.");
        }
    }

    public function validatePath(string $path): void
    {
        $normalized = $this->cleanPath($path);
        $base = rtrim($this->basePath, \DIRECTORY_SEPARATOR);
        if (!str_starts_with($normalized, $base)) {
            throw new FileSystemException("Path must be in base path: '{$path}'.");
        }

        // Если файл существует — проверяем через realpath для защиты от symlink-атак
        $realPath = realpath($path);
        if (false !== $realPath && !str_starts_with($realPath, $base)) {
            throw new FileSystemException("Resolved '{$path}' is outside of the base directory.");
        }
    }

    public function includeFile(string $file): void
    {
        $this->validatePath($file);
        if (file_exists($file)) {
            include $file;
        }
    }

    public function includeFileOrDie(string $file, ?string $errorMessage = null): void
    {
        $this->validatePath($file);
        if (file_exists($file)) {
            include $file;

            return;
        }

        throw new FileSystemException($errorMessage ?? "Unable to include file: '{$file}'.");
    }

    public function writeFile(string $fileName, mixed $data, int $flags = 0, $context = null): int
    {
        $this->validatePath($fileName);
        $result = file_put_contents($fileName, $data, $flags, $context);
        if (false === $result) {
            throw new FileSystemException("Failed to write file: '{$fileName}'.");
        }

        return $result;
    }

    public function writeFileSafe(string $fileName, mixed $data, int $flags = 0, $context = null): int
    {
        $this->validatePath($fileName);
        $path = dirname($fileName);
        $tmp = tempnam($path, '.tmp.');
        $result = file_put_contents($tmp, $data, $flags, $context);
        if (false === $result || false === rename($tmp, $fileName)) {
            @unlink($tmp);

            throw new FileSystemException("Failed to write file: '{$fileName}'.");
        }

        return $result;
    }

    public function readFile(string $fileName, $context = null, int $offset = 0, ?int $length = null): string
    {
        $this->validatePath($fileName);
        $result = file_get_contents($fileName, false, $context, $offset, $length);
        if (false === $result) {
            throw new FileSystemException("Failed to read file: '{$fileName}'.");
        }

        return $result;
    }

    private function cleanPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        $parts = explode('/', $path);
        $absolutes = [];
        $isAbsolute = str_starts_with($path, '/') || ($this->isWindows && preg_match('~^[A-Z]:(\\\|/)~i', $path));

        foreach ($parts as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }

            if ('..' === $part) {
                if (!empty($absolutes)) {
                    array_pop($absolutes);
                }
            } else {
                $absolutes[] = $part;
            }
        }

        $result = implode(\DIRECTORY_SEPARATOR, $absolutes);
        if ($isAbsolute) {
            $result = \DIRECTORY_SEPARATOR . $result;
        }

        return $result;
    }
}
