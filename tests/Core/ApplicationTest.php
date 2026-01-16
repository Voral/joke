<?php

namespace Vasoft\Joke\Tests\Core;

use Vasoft\Joke\Core\Application;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\ServiceContainer;

class ApplicationTest extends TestCase
{
    public function testExecuteDefaultHtml(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer()
        );
        ob_start();
        $app->handle(new HttpRequest(server:['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']));
        $output = ob_get_clean();
        self::assertSame('Hi', $output);
    }

    public function testExecuteDefaultJson(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer()
        );
        ob_start();
        $app->handle(new HttpRequest(server:['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/json/Alex']));
        $output = ob_get_clean();
        self::assertSame('{"fio":"Alex"}', $output);
    }
}
