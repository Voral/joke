<?php

declare(strict_types=1);

namespace Application;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestDox;
use Vasoft\Joke\Support\FileSystem;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Support\FileSystem
 */
#[CoversClass(FileSystem::class)]
#[TestDox('FileSystem — единый сервис знаний о путях проекта. windows')]
final class FileSystemWindowsTest extends TestCase
{
    use PHPMock;

    #[TestDox('isAbsolute возвращает true для Windows-абсолютного пути')]
    #[RunInSeparateProcess]
    public function testIsAbsoluteWindows(): void
    {
        $realPath = $this->getFunctionMock('Vasoft\Joke\Support', 'realpath');
        $realPath->expects(self::once())->willReturnCallback(static fn($path) => $path);
        $isDir = $this->getFunctionMock('Vasoft\Joke\Support', 'is_dir');
        $isDir->expects(self::once())->willReturn(true);
        $substr = $this->getFunctionMock('Vasoft\Joke\Support', 'strtoupper');
        $substr->expects(self::once())->willReturn('WIN');

        $fileSystem = new FileSystem('c:\var\www');

        self::assertTrue($fileSystem->isAbsolute('C:/Windows/System32'));
        self::assertTrue($fileSystem->isAbsolute('d:\data'));
    }
}
