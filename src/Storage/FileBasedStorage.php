<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage;

use Vasoft\Joke\Contract\Storage\StorageInterface;
use Vasoft\Joke\Storage\Exceptions\StorageException;

/**
 * Файловое хранилище ключ-значение с поддержкой TTL.
 *
 * Каждый ключ хранится в отдельном JSON-файле с шардированием по MD5-хешу.
 * Конкурентный доступ обеспечивается через flock (LOCK_EX).
 *
 * Структура директории:
 * {storagePath}/
 *   data/
 *     ab/
 *       ab12cd34...json   # Файл записи
 *     cd/
 *       cd34ef56...json
 *
 * Формат файла:
 * {
 *     "value": "42",
 *     "expires_at": 1712345678
 * }
 *
 * @see StorageInterface
 */
class FileBasedStorage implements StorageInterface
{
    /**
     * @param string $storagePath    Путь к директории хранения данных
     * @param int    $directoryLevel Количество уровней вложенности шардирования (по умолчанию 2)
     * @param int    $shardLength    Длина сегмента хеша для имени директории (по умолчанию 2)
     *
     * @throws StorageException Если не удалось создать директорию хранения
     */
    public function __construct(
        private readonly string $storagePath,
        private readonly int $directoryLevel = 2,
        private readonly int $shardLength = 2,
    ) {
        if (!is_dir($storagePath) && !mkdir($storagePath, 0o755, true) && !is_dir($storagePath)) {
            throw new StorageException(
                sprintf('Не удалось создать директорию хранилища: %s', $storagePath),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        $path = $this->getFilePath($key);
        if (!file_exists($path)) {
            return null;
        }

        $data = $this->readFile($path);
        if (null === $data) {
            return null;
        }

        if (null !== $data['expires_at'] && $data['expires_at'] <= time()) {
            @unlink($path);

            return null;
        }

        return $data['value'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, string $value, ?int $ttl = null): bool
    {
        $path = $this->getFilePath($key);
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new StorageException(sprintf('Не удалось создать директорию: %s', $dir));
        }

        $data = [
            'value' => $value,
            'expires_at' => null !== $ttl ? time() + $ttl : null,
        ];

        // Атомарная запись: сначала во временный файл, затем переименование
        $tmp = tempnam($dir, '.tmp.');
        if (false === $tmp) {
            throw new StorageException('Не удалось создать временный файл');
        }

        file_put_contents($tmp, json_encode($data, JSON_THROW_ON_ERROR));
        rename($tmp, $path);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $path = $this->getFilePath($key);
        if (!file_exists($path)) {
            return false;
        }

        return @unlink($path);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $key): bool
    {
        return null !== $this->get($key);
    }

    /**
     * {@inheritDoc}
     *
     * Реализует атомарный инкремент через flock.
     * Блокировка удерживается только на время чтения + инкремента + записи.
     * TTL применяется ТОЛЬКО при создании нового ключа.
     */
    public function increment(string $key, int $ttl): int
    {
        $path = $this->getFilePath($key);
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            throw new StorageException(sprintf('Не удалось создать директорию: %s', $dir));
        }

        $fp = @fopen($path, 'c+');
        if (false === $fp) {
            throw new StorageException(sprintf('Не удалось открыть файл: %s', $path));
        }

        try {
            // Эксклюзивная блокировка — ожидает освобождения
            if (!flock($fp, LOCK_EX)) {
                throw new StorageException(sprintf('Не удалось получить блокировку на файл: %s', $path));
            }

            $content = stream_get_contents($fp);
            $data = ('' !== $content && false !== $content) ? json_decode($content, true) : null;

            $now = time();

            // Если ключ не существует или истекло время жизни
            if (null === $data || (isset($data['expires_at']) && null !== $data['expires_at'] && $data['expires_at'] <= $now)) {
                $currentValue = 0;
                $expiresAt = $now + $ttl;
            } else {
                $currentValue = (int) ($data['value'] ?? 0);
                $expiresAt = $data['expires_at'] ?? ($now + $ttl);
            }

            $newValue = $currentValue + 1;

            $newData = json_encode(
                [
                    'value' => (string) $newValue,
                    'expires_at' => $expiresAt,
                ],
                JSON_THROW_ON_ERROR,
            );

            // Очистка и перезапись файла
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $newData);
            fflush($fp);

            return $newValue;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * {@inheritDoc}
     *
     * Сканирует директорию данных и удаляет файлы с истёкшим сроком жизни.
     * Использует глобальный lock-файл для предотвращения одновременной очистки.
     */
    public function cleanup(): int
    {
        $count = 0;
        $dataDir = $this->storagePath . '/data';

        if (!is_dir($dataDir)) {
            return 0;
        }

        $lockFile = $this->storagePath . '/cleanup.lock';
        $fp = @fopen($lockFile, 'c');
        if (false === $fp) {
            return 0;
        }

        // Неблокирующая попытка — если другой процесс уже чистит, пропускаем
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);

            return 0;
        }

        try {
            $now = time();
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dataDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && 'json' === $file->getExtension()) {
                    $content = @file_get_contents($file->getPathname());
                    if (false === $content) {
                        continue;
                    }

                    $data = json_decode($content, true);
                    if (isset($data['expires_at']) && null !== $data['expires_at'] && $data['expires_at'] <= $now) {
                        @unlink($file->getPathname());
                        ++$count;
                    }
                }
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return $count;
    }

    /**
     * Читает и парсит JSON-файл.
     *
     * @param string $path Полный путь к файлу
     *
     * @return null|array{value: string, expires_at: ?int} null при ошибке чтения
     */
    private function readFile(string $path): ?array
    {
        $content = @file_get_contents($path);
        if (false === $content) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['value'])) {
            return null;
        }

        return [
            'value' => (string) $data['value'],
            'expires_at' => isset($data['expires_at']) ? (null === $data['expires_at'] ? null : (int) $data['expires_at']) : null,
        ];
    }

    /**
     * Возвращает путь к файлу на основе MD5-хеша ключа.
     *
     * Формирует структуру: {storagePath}/data/{shard1}/{shard2}/{fullHash}.json
     *
     * @param string $key Ключ записи
     *
     * @return string Полный путь к файлу
     */
    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        $parts = [];
        for ($i = 0; $i < $this->directoryLevel; ++$i) {
            $parts[] = substr($hash, $i * $this->shardLength, $this->shardLength);
        }

        return sprintf(
            '%s/data/%s/%s.json',
            $this->storagePath,
            implode('/', $parts),
            $hash,
        );
    }
}