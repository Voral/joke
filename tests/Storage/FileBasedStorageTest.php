<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Storage\FileBasedStorage;
use Vasoft\Joke\Storage\StorageException;

/**
 * Тесты для FileBasedStorage.
 */
class FileBasedStorageTest extends TestCase
{
    private FileBasedStorage $storage;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/joke_storage_test_' . uniqid();
        $this->storage = new FileBasedStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Удаляем временные файлы
        $this->storage->clear();
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
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

    public function testTtlExpiration(): void
    {
        $result = $this->storage->set('test_key', 'test_value', 1); // 1 second TTL
        
        $this->assertTrue($result);
        $this->assertEquals('test_value', $this->storage->get('test_key'));
        
        // Wait for TTL to expire
        sleep(2);
        
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

    public function testConcurrentAccess(): void
    {
        // Запускаем несколько процессов для теста конкурентного доступа
        $numProcesses = 5;
        $incrementsPerProcess = 20;
        
        for ($i = 0; $i < $numProcesses; $i++) {
            $this->runProcess($incrementsPerProcess);
        }
        
        // Ждем завершения всех процессов
        sleep(1);
        
        $value = $this->storage->get('concurrent_counter');
        
        // Проверяем, что все инкременты были применены
        $expected = $numProcesses * $incrementsPerProcess;
        $this->assertEquals((string)$expected, $value);
    }

    /**
     * Запускает отдельный процесс для инкремента счетчика.
     */
    private function runProcess(int $increments): void
    {
        $tempDir = $this->tempDir;
        $code = <<< 'PHP'
<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Vasoft\Joke\Storage\FileBasedStorage;

\$storage = new FileBasedStorage('{$tempDir}');

for (\$i = 0; \$i < 20; \$i++) {
    \$storage->cas('concurrent_counter', function (\$value) {
        if (\$value === null) {
            return '1';
        }
        return (string)((int)\$value + 1);
    });
}
PHP;
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'joke_test_');
        file_put_contents($tmpFile, $code);
        
        $cmd = "php {$tmpFile} > /dev/null 2>&1 &";
        exec($cmd);
        
        unlink($tmpFile);
    }
}
