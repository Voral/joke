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
        $di = new ServiceContainer();
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            $di
        );
        ob_start();
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $app->handle($request);
        $output = ob_get_clean();
        self::assertStringContainsString('<li><a href="/name/Alex">Hi Alex</a>', $output);
        self::assertSame($request, $di->get(HttpRequest::class));
    }

    public function testExecuteDefaultJson(): void
    {
        $app = new Application(
            dirname(__DIR__, 2),
            '/routes/web.php',
            new ServiceContainer()
        );
        ob_start();
        $app->handle(new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/json/Alex']));
        $output = ob_get_clean();
        self::assertSame('{"fio":"Alex"}', $output);
    }
}
