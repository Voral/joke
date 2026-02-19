<?php

declare(strict_types=1);

namespace Vasoft\Joke\Support\Normalizers;

use Vasoft\Joke\Config\Exceptions\ConfigException;

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
final readonly class Path
{
    /**
     * Абсолютный базовый путь к корневой директории.
     * Всегда заканчивается разделителем директории.
     */
    public string $basePath;
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
        $this->isWindows = 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
        if (!self::isAbsolute($basePath)) {
            throw new ConfigException("Path must be absolute: {$basePath}");
        }
        if (!is_dir($basePath)) {
            throw new ConfigException("Path must be a directory: {$basePath}");
        }
        $this->basePath = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
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
     * - Для Windows: проверяет наличие буквы диска (например, "C:").
     * - Для Unix-систем: проверяет наличие начального слэша ("/").
     *
     * @param string $path путь для проверки
     *
     * @return bool true, если путь абсолютный, иначе False
     */
    public function isAbsolute(string $path): bool
    {
        if ($this->isWindows) {
            return (bool) preg_match('~^[A-Z]:~i', $path);
        }

        return str_starts_with($path, \DIRECTORY_SEPARATOR);
    }
}
