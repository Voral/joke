<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Storage\ArrayStorage;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Storage\ArrayStorage
 */
final class ArrayStorageTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $storage = new ArrayStorage();
        $storage->set('key1', 'value1');
        self::assertSame('value1', $storage->get('key1'));
    }

    public function testGetNonExistent(): void
    {
        $storage = new ArrayStorage();
        self::assertNull($storage->get('non_existent'));
    }

    public function testDelete(): void
    {
        $storage = new ArrayStorage();
        $storage->set('key1', 'value1');
        self::assertTrue($storage->delete('key1'));
        self::assertNull($storage->get('key1'));
    }

    public function testDeleteNonExistent(): void
    {
        $storage = new ArrayStorage();
        self::assertFalse($storage->delete('non_existent'));
    }

    public function testExists(): void
    {
        $storage = new ArrayStorage();
        $storage->set('key1', 'value1');
        self::assertTrue($storage->exists('key1'));
        self::assertFalse($storage->exists('non_existent'));
    }

    public function testTtlExpiration(): void
    {
        $now = 1000;
        $storage = new ArrayStorage($now);
        $storage->set('key1', 'value1', 10);
        self::assertSame('value1', $storage->get('key1'));

        $storage->setTime($now + 11);
        self::assertNull($storage->get('key1'));
    }

    public function testIncrementNewKey(): void
    {
        $storage = new ArrayStorage(1000);
        $result = $storage->increment('counter', 60);
        self::assertSame(1, $result);
    }

    public function testIncrementExistingKey(): void
    {
        $storage = new ArrayStorage(1000);
        $storage->increment('counter', 60);
        $result = $storage->increment('counter', 60);
        self::assertSame(2, $result);
    }

    public function testIncrementResetsAfterTtl(): void
    {
        $now = 1000;
        $storage = new ArrayStorage($now);
        $storage->increment('counter', 10);
        self::assertSame(1, (int) $storage->get('counter'));

        $storage->setTime($now + 11);
        $result = $storage->increment('counter', 10);
        self::assertSame(1, $result);
    }

    public function testCleanup(): void
    {
        $now = 1000;
        $storage = new ArrayStorage($now);
        $storage->set('key1', 'value1', 10);
        $storage->set('key2', 'value2', 20);
        $storage->set('key3', 'value3'); // Без TTL

        $storage->setTime($now + 15);
        $cleaned = $storage->cleanup();
        self::assertSame(1, $cleaned);
        self::assertNull($storage->get('key1'));
        self::assertSame('value2', $storage->get('key2'));
        self::assertSame('value3', $storage->get('key3'));
    }

    public function testSetWithoutTtl(): void
    {
        $storage = new ArrayStorage(1000);
        $storage->set('permanent', 'always');
        $storage->setTime(999999);
        self::assertSame('always', $storage->get('permanent'));
    }
}