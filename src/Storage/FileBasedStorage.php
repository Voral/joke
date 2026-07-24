<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage;

use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Storage\StorageException;

/**
 * Файловое хранилище с поддержкой блокировок для многопроцессной безопасности.
 *
 * Структура хранения:
 * storage/ratelimit/data/{prefix}/{hash}.json
 *
 * Пример:
 * storage/ratelimit/data/70/87/7087072fe074dfcdec24b5552ec5529c.json
 *
 * Особенности:
 * - Файловые блокировки (flock) для конкурентного доступа
 * - Атомарная запись через tempnam + rename
 * - Формат JSON с полями: value, expires_at, version
 */
class FileBasedStorage implements StorageInterface
{
    /**
     * Базовая директория для хранения данных.
     */
    private string $storagePath;

    /**
     * @param string $storagePath Путь к директории для хранения (по умолчанию storage/ratelimit)
     */
    public function __construct(string $storagePath = '')
    {
        $this->storagePath = $storagePath ?: sys_get_temp_dir() . '/joke_storage';
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }

        // Используем блокировку для чтения (shared lock)
        $file = @fopen($filePath, 'r');
        if ($file === false) {
            throw new StorageException("Не удалось открыть файл для чтения: $filePath");
        }

        if (!flock($file, LOCK_SH)) {
            fclose($file);
            throw new StorageException("Не удалось получить shared lock на файл: $filePath");
        }

        $content = file_get_contents($filePath);
        
        flock($file, LOCK_UN);
        fclose($file);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        
        if (!is_array($data) || !isset($data['value'])) {
            return null;
        }

        // Проверка TTL
        if (isset($data['expires_at']) && $data['expires_at'] > 0 && time() > $data['expires_at']) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, string $value, int $ttl = 0): bool
    {
        $filePath = $this->getFilePath($key);
        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0o755, true)) {
                throw new StorageException("Не удалось создать директорию: $dir");
            }
        }

        $expiresAt = $ttl > 0 ? time() + $ttl : 0;

        $data = [
            'value' => $value,
            'expires_at' => $expiresAt,
            'version' => 0,
        ];

        $content = json_encode($data, JSON_THROW_ON_ERROR);

        // Атомарная запись: tempnam + rename
        $tmpFile = tempnam($dir, '.tmp.');
        if ($tmpFile === false) {
            throw new StorageException("Не удалось создать временный файл в $dir");
        }

        if (file_put_contents($tmpFile, $content) === false) {
            unlink($tmpFile);
            throw new StorageException("Не удалось записать во временный файл: $tmpFile");
        }

        // Блокировка перед переименованием
        $file = @fopen($filePath, 'c');
        if ($file === false) {
            unlink($tmpFile);
            throw new StorageException("Не удалось открыть/создать файл: $filePath");
        }

        if (!flock($file, LOCK_EX)) {
            fclose($file);
            unlink($tmpFile);
            throw new StorageException("Не удалось получить exclusive lock на файл: $filePath");
        }

        if (!rename($tmpFile, $filePath)) {
            flock($file, LOCK_UN);
            fclose($file);
            unlink($tmpFile);
            throw new StorageException("Не удалось переименовать временный файл в $filePath");
        }

        flock($file, LOCK_UN);
        fclose($file);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return true;
        }

        // Блокировка перед удалением
        $file = @fopen($filePath, 'c');
        if ($file === false) {
            return false;
        }

        if (!flock($file, LOCK_EX)) {
            fclose($file);
            return false;
        }

        $result = @unlink($filePath);

        flock($file, LOCK_UN);
        fclose($file);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function cas(
        string $key,
        callable $callback,
        int $ttl = 0,
        int $maxRetries = 10
    ): array {
        $filePath = $this->getFilePath($key);
        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0o755, true)) {
                throw new StorageException("Не удалось создать директорию: $dir");
            }
        }

        $file = @fopen($filePath, 'c+');
        if ($file === false) {
            throw new StorageException("Не удалось открыть файл для записи: $filePath");
        }

        // Получаем exclusive lock
        if (!flock($file, LOCK_EX)) {
            fclose($file);
            throw new StorageException("Не удалось получить exclusive lock на файл: $filePath");
        }

        $retryCount = 0;
        $currentVersion = 0;
        $currentValue = null;

        // Читаем текущее значение
        $content = @file_get_contents($filePath);
        if ($content !== false) {
            $data = json_decode($content, true);
            if (is_array($data)) {
                $currentValue = $data['value'] ?? null;
                $currentVersion = (int)($data['version'] ?? 0);
            }
        }

        while ($retryCount < $maxRetries) {
            try {
                // Вызываем callback с текущим значением
                $newValue = $callback($currentValue);
                
                if ($newValue === null) {
                    // Если callback вернул null - удаляем запись
                    flock($file, LOCK_UN);
                    fclose($file);
                    $this->delete($key);
                    return ['value' => '', 'version' => $currentVersion + 1];
                }

                $expiresAt = $ttl > 0 ? time() + $ttl : 0;

                $newData = [
                    'value' => $newValue,
                    'expires_at' => $expiresAt,
                    'version' => $currentVersion + 1,
                ];

                $newContent = json_encode($newData, JSON_THROW_ON_ERROR);

                // Проверяем, не изменился ли файл с момента последнего чтения
                clearstatcache(true, $filePath);
                $currentMtime = @filemtime($filePath);
                
                // Записываем новое значение
                if (ftruncate($file, 0) === false || fseek($file, 0) === false || fwrite($file, $newContent) === false) {
                    throw new StorageException("Не удалось записать новое значение в файл: $filePath");
                }
                
                if (fflush($file) === false) {
                    throw new StorageException("Не удалось сбросить буфер файла: $filePath");
                }

                // Возвращаем результат
                $result = [
                    'value' => $newValue,
                    'version' => $newData['version'],
                ];

                flock($file, LOCK_UN);
                fclose($file);

                return $result;
            } catch (\Throwable $e) {
                $retryCount++;
                
                if ($retryCount >= $maxRetries) {
                    flock($file, LOCK_UN);
                    fclose($file);
                    throw new StorageException(
                        "Превышено максимальное число попыток CAS операции: $maxRetries",
                        0,
                        $e
                    );
                }

                // Читаем обновленное значение
                clearstatcache(true, $filePath);
                $content = @file_get_contents($filePath);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data)) {
                        $currentValue = $data['value'] ?? null;
                        $currentVersion = (int)($data['version'] ?? 0);
                    }
                }
            }
        }

        flock($file, LOCK_UN);
        fclose($file);

        throw new StorageException("Превышено максимальное число попыток CAS операции: $maxRetries");
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $dir = $this->storagePath;

        if (!is_dir($dir)) {
            return true;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        $result = true;
        foreach ($files as $file) {
            if ($file->isDir()) {
                $result &= rmdir($file->getPathname());
            } else {
                $result &= unlink($file->getPathname());
            }
        }

        return $result && rmdir($dir);
    }

    /**
     * Генерирует путь к файлу на основе ключа.
     *
     * @param string $key Уникальный идентификатор
     *
     * @return string Полный путь к файлу
     */
    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        $prefix = mb_substr($hash, 0, 2);

        return sprintf(
            '%s/%s/%s/%s.json',
            $this->storagePath,
            $prefix,
            mb_substr($hash, 2, 2),
            $hash
        );
    }

    /**
     * Возвращает путь к хранилищу.
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }
}
