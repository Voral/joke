<?php

namespace Vasoft\Joke\Tests\Core\Collections;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Vasoft\Joke\Core\Collections\Session;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Exceptions\SessionException;

class SessionTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testSaveNotModified(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $session = new Session(['foo' => 'bar']);
        $session->save();
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    #[RunInSeparateProcess]
    public function testSave(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $session = new Session(['foo' => 'bar']);
        $session->set('foo', 'bar');
        $session->save();
        self::assertArrayHasKey('foo', $_SESSION);
    }

    #[RunInSeparateProcess]
    public function testSaveWhenReset(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $session = new Session(['foo' => 'bar']);
        $session->reset(['foo' => 'bar']);
        $session->save();
        self::assertArrayHasKey('foo', $_SESSION);
    }

    #[RunInSeparateProcess]
    public function testReadonlyMode(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $session = new Session(['foo' => 'bar']);
        $session->reset(['foo' => 'bar']);

        self::expectException(SessionException::class);
        self::expectExceptionMessage('Readonly session mode. Can\'t write');
        $session->save();
    }

    #[RunInSeparateProcess]
    public function testLoadReadonlyMode(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $session = new Session([]);

        self::expectException(SessionException::class);
        self::expectExceptionMessage('Readonly session mode. Can\'t write');
        $session->load();
    }

    #[RunInSeparateProcess]
    public function testUnset(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $session = new Session(['foo' => 'bar']);
        $session->reset(['foo' => 'bar']);
        $session->save();
        self::assertArrayHasKey('foo', $_SESSION);
        $session->unset('foo');
        $session->save();
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    #[RunInSeparateProcess]
    public function testUnsetAndSet(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $session = new Session(['foo' => 'bar']);
        $session->unset('foo');
        $session->set('foo', 'bar1');
        $session->save();
        self::assertArrayHasKey('foo', $_SESSION);
    }

    #[RunInSeparateProcess]
    public function testUnsetAndReset(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $session = new Session(['foo' => 'bar']);
        $session->unset('foo');
        $session->reset(['foo' => 'bar']);
        $session->save();
        self::assertArrayHasKey('foo', $_SESSION);
    }
}
