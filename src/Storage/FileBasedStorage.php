<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage;

use Vasoft\Joke\Contract\RateLimit\StorageInterface;
use Vasoft\Joke\Storage\Exceptions\StorageException;

/**
 * Файловое хранилище для rate limiting счётчиков.
 *
 * Каждый счётчик хранится в отдельном файле: dir/key.
 * Содержимое файла: "value:expiresAt" (разделено двоеточием).
 *
 * Реализует локальную очистку (вариант C) при increment(),
 * и редкий полный cleanup по необходимости.
 *
 * TODO: добавить полный cleanup по необходимости
 */
final class FileBasedStorage implements StorageInterface
{
    public function __construct(
        private readonly string $dir,
    ) {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new StorageException("Cannot create storage directory: {$dir}");
            }
        }

        if (!is_writable($dir)) {
            throw new StorageException("Storage directory is not writable: {$dir}");
        }
    }

    public function increment(string $key, int $ttl = 3600): int
    {
        $path = $this->getPath($key);
        $this->cleanupExpiredKey($path);

        $current = $this->readCounter($path);
        $newValue = $current + 1;

        $data = "{$newValue}:" . (time() + $ttl);
        if (@file_put_contents($path, $data, LOCK_EX) === false) {
            throw new StorageException("Cannot write to storage: {$path}");
        }

        return $newValue;
    }

    public function get(string $key): int
    {
        $path = $this->getPath($key);
        $this->cleanupExpiredKey($path);

        return $this->readCounter($path);
    }

    public function clear(): void
    {
        $files = glob($this->dir . '/*', GLOB_NOSORT);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function getPath(string $key): string
    {
        return $this->dir . '/' . hash('sha256', $key);
    }

    private function readCounter(string $path): int
    {
        if (!is_file($path)) {
            return 0;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return 0;
        }

        [$value] = explode(':', $content, 2) + [0];

        return (int)$value;
    }

    private function cleanupExpiredKey(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return;
        }

        [, $expiresAt] = explode(':', $content, 2) + [0, 0];
        $expiresAt = (int)$expiresAt;

        if ($expiresAt <= time()) {
            @unlink($path);
        }
    }
}
