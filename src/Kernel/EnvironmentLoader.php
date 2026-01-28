<?php

namespace Vasoft\Joke\Kernel;

/**
 * Загрузчик переменных из .env файлов
 *
 * Поддерживает три уровня конфигурации:
 * 1. Базовый файл .env
 * 2. Файл, специфичный для текущего окружения - .env.{envName}
 * 3. Локальный файл переопределений .env.{localName}
 *
 * .env.{localName} - не загружается дважды и если текущее окружение тестовое
 *
 * Все значения парсятся с автоматическим приведением типов:
 * - строки в кавычках всегда остаются строками
 * - числа - int/float
 * - true/false - boolean
 * - null или пустое значение - null
 * - строки начинающиеся с # - комментарии
 */
readonly class EnvironmentLoader
{
    private string $basePath;

    public function __construct(
        string $basePath,
    ) {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Загружает переменные окружения из соответствующих .env-файлов
     * @param string $envName имя текущего окружения
     * @param string $localName имя локального окружения. Файл .env.{localName} загружается только один раз, для всех окружений исключая testing
     * @param string $testName тестовое окружение.
     * @return array<string, string|float|int|bool|null>
     */
    public function load(string $envName, string $localName, string $testName): array
    {
        $files = $this->getFileList($envName, $localName, $testName);
        $vars = [];
        foreach ($files as $file) {
            $this->parseFile($file, $vars);
        }
        return $vars;
    }

    private function parseFile(string $fileName, array &$vars): void
    {
        $path = $this->basePath . $fileName;
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#')) {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, null);

            $key = trim(strtoupper($key));
            if ($value === null) {
                $vars[$key] = null;
                continue;
            }
            $vars[$key] = $this->normalizeValue($value);
        }
    }

    private function normalizeValue(string $value): int|float|string|bool|null
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return trim(stripcslashes($value), '"');
        }
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return trim(stripcslashes($value), "'");
        }
        if (!is_numeric($value)) {
            return $this->normalizeString($value);
        }
        if (strpbrk($value, '.eE') === false) {
            return (int)$value;
        }
        return (float)$value;
    }

    private function normalizeString(string $value): string|bool|null
    {
        return match ($value) {
            'false' => false,
            'true' => true,
            'null' => null,
            default => $value,
        };
    }

    private function getFileList(string $envName, string $localName, string $testName): array
    {
        $files = ['.env'];
        if ($envName !== '' && $envName !== $localName) {
            $files[] = '.env.' . $envName;
        }
        if ($envName !== $testName) {
            $files[] = '.env.' . $localName;
        }
        return $files;
    }
}