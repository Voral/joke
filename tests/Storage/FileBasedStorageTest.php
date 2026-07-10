<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Storage\FileBasedStorage;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Storage\FileBasedStorage
 */
final class FileBasedStorageTest extends TestCase
{
    private string $tempDir;
    private FileBasedStorage $storage;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/joke_storage_test_' . uniqid();
        $this->storage = new FileBasedStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testSetAndGet(): void
    {
        $this->storage->set('key1', 'value1');
        self::assertSame('value1', $this->storage->get('key1'));
    }

    public function testGetNonExistent(): void
    {
        self::assertNull($this->storage->get('non_existent'));
    }

    public function testDelete(): void
    {
        $this->storage->set('key1', 'value1');
        self::assertTrue($this->storage->delete('key1'));
        self::assertNull($this->storage->get('key1'));
    }

    public function testDeleteNonExistent(): void
    {
        self::assertFalse($this->storage->delete('non_existent'));
    }

    public function testExists(): void
    {
        $this->storage->set('key1', 'value1');
        self::assertTrue($this->storage->exists('key1'));
        self::assertFalse($this->storage->exists('non_existent'));
    }

    public function testTtlExpiration(): void
    {
        $this->storage->set('key1', 'value1', 0);
        self::assertNull($this->storage->get('key1'));
    }

    public function testIncrementNewKey(): void
    {
        $result = $this->storage->increment('counter', 60);
        self::assertSame(1, $result);
    }

    public function testIncrementExistingKey(): void
    {
        $this->storage->increment('counter', 60);
        $result = $this->storage->increment('counter', 60);
        self::assertSame(2, $result);
    }

    public function testSetWithoutTtl(): void
    {
        $this->storage->set('permanent', 'always');
        self::assertSame('always', $this->storage->get('permanent'));
    }

    public function testCleanup(): void
    {
        $this->storage->set('key1', 'value1', 0);
        $this->storage->set('key2', 'value2', 3600);
        $cleaned = $this->storage->cleanup();
        self::assertSame(1, $cleaned);
        self::assertNull($this->storage->get('key1'));
        self::assertSame('value2', $this->storage->get('key2'));
    }

    public function testSpecialCharactersInKey(): void
    {
        $this->storage->set('user:email@test.com/path?query=1', 'found');
        self::assertSame('found', $this->storage->get('user:email@test.com/path?query=1'));
    }

    public function testLongKey(): void
    {
        $longKey = str_repeat('a', 1000);
        $this->storage->set($longKey, 'long_value');
        self::assertSame('long_value', $this->storage->get($longKey));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }
}