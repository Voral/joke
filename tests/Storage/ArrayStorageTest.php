<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Storage\ArrayStorage;

/**
 * Тесты для ArrayStorage.
 */
class ArrayStorageTest extends TestCase
{
    private ArrayStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
    }

    public function testSetAndGet(): void
    {
        $result = $this->storage->set('test_key', 'test_value', 3600);
        
        $this->assertTrue($result);
        $this->assertEquals('test_value', $this->storage->get('test_key'));
    }

    public function testGetNonExistentKey(): void
    {
        $this->assertNull($this->storage->get('non_existent_key'));
    }

    public function testDelete(): void
    {
        $this->storage->set('test_key', 'test_value');
        
        $result = $this->storage->delete('test_key');
        
        $this->assertTrue($result);
        $this->assertNull($this->storage->get('test_key'));
    }

    public function testCas(): void
    {
        $result = $this->storage->cas('counter', function (?string $value) {
            if ($value === null) {
                return '0';
            }
            
            return (string)((int)$value + 1);
        });

        $this->assertEquals('0', $result['value']);
        $this->assertEquals(1, $result['version']);

        // Second increment
        $result = $this->storage->cas('counter', function (?string $value) {
            if ($value === null) {
                return '0';
            }
            
            return (string)((int)$value + 1);
        });

        $this->assertEquals('1', $result['value']);
        $this->assertEquals(2, $result['version']);
    }

    public function testCasWithNull(): void
    {
        $this->storage->set('test_key', 'test_value');
        
        $result = $this->storage->cas('test_key', function (?string $value) {
            return null; // Удаление
        });

        $this->assertEquals('', $result['value']);
        $this->assertEquals(1, $result['version']);
        $this->assertNull($this->storage->get('test_key'));
    }

    public function testClear(): void
    {
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        
        $result = $this->storage->clear();
        
        $this->assertTrue($result);
        $this->assertNull($this->storage->get('key1'));
        $this->assertNull($this->storage->get('key2'));
    }

    public function testGetAll(): void
    {
        $this->storage->set('key1', 'value1');
        $this->storage->set('key2', 'value2');
        
        $all = $this->storage->getAll();
        
        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
        $this->assertEquals('value1', $all['key1']['value']);
    }
}
