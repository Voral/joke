<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Storage\FileBasedStorage;
use Vasoft\Joke\Storage\Exceptions\StorageException;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Storage\FileBasedStorage
 */
final class FileBasedStorageTest extends TestCase
{
    private string $tmpDir;
    private FileBasedStorage $storage;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/jest_fbs_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->storage = new FileBasedStorage($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->storage->clear();
        @rmdir($this->tmpDir);
    }

    public function testIncrementAndGet(): void
    {
        $result = $this->storage->increment('test_key');
        self::assertSame(1, $result);

        $result = $this->storage->increment('test_key');
        self::assertSame(2, $result);

        $value = $this->storage->get('test_key');
        self::assertSame(2, $value);
    }

    public function testGetNonexistent(): void
    {
        $value = $this->storage->get('nonexistent_key');
        self::assertSame(0, $value);
    }

    public function testTtlExpiry(): void
    {
        $this->storage->increment('ttl_key', 1);
        $value = $this->storage->get('ttl_key');
        self::assertSame(1, $value);

        sleep(2);

        $value = $this->storage->get('ttl_key');
        self::assertSame(0, $value);
    }

    public function testClear(): void
    {
        $this->storage->increment('key1');
        $this->storage->increment('key2');

        $this->storage->clear();

        $value1 = $this->storage->get('key1');
        $value2 = $this->storage->get('key2');

        self::assertSame(0, $value1);
        self::assertSame(0, $value2);
    }

    public function testDirectoryCreation(): void
    {
        $newDir = sys_get_temp_dir() . '/jest_fbs_new_' . uniqid();
        self::assertFalse(is_dir($newDir));

        $storage = new FileBasedStorage($newDir);
        self::assertTrue(is_dir($newDir));

        $storage->clear();
        @rmdir($newDir);
    }

    public function testDirectoryNotWritable(): void
    {
        $this->expectException(StorageException::class);

        new FileBasedStorage('/root/jest_readonly');
    }

    public function testMultipleKeys(): void
    {
        $this->storage->increment('key1');
        $this->storage->increment('key1');
        $this->storage->increment('key2');

        self::assertSame(2, $this->storage->get('key1'));
        self::assertSame(1, $this->storage->get('key2'));
    }

    public function testIncrementWithCustomTtl(): void
    {
        $this->storage->increment('key1', 1);
        $this->storage->increment('key2', 100);

        sleep(2);

        self::assertSame(0, $this->storage->get('key1'));
        self::assertSame(1, $this->storage->get('key2'));
    }
}
