<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Middleware;

use Vasoft\Joke\Middleware\Exceptions\CsrfMismatchException;
use Vasoft\Joke\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\ResponseStatus;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Middleware\CsrfMiddleware
 */
final class CsrfMiddlewareTest extends TestCase
{
    public function testHandle(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware();
        $middleware->handle($request, static fn() => null);
        self::assertNotNull($request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME));
        self::assertNotEmpty($request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME));
    }

    public function testHandlePostSuccessHeader(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware();
        $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        $request = new HttpRequest(server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/csrf',
            'HTTP_' . str_replace('-', '_', strtoupper(CsrfMiddleware::CSRF_TOKEN_HEADER)) => $token,
        ]);
        $request->session->set(CsrfMiddleware::CSRF_TOKEN_NAME, $token);

        self::assertSame('success', $middleware->handle($request, static fn() => 'success'));
    }

    public function testHandlePostSuccessPost(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware();
        $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        $request = new HttpRequest(post: [CsrfMiddleware::CSRF_TOKEN_NAME => $token], server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/csrf',
            'HTTP_' . str_replace('-', '_', strtoupper(CsrfMiddleware::CSRF_TOKEN_HEADER)) => 'unknown',
        ]);
        $request->session->set(CsrfMiddleware::CSRF_TOKEN_NAME, $token);
        self::assertSame('success', $middleware->handle($request, static fn() => 'success'));
    }

    public function testHandlePostSuccessGet(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware();
        $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        $request = new HttpRequest(
            get: [CsrfMiddleware::CSRF_TOKEN_NAME => $token],
            post: [CsrfMiddleware::CSRF_TOKEN_NAME => 'unknown2'],
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/csrf',
                'HTTP_' . str_replace('-', '_', strtoupper(CsrfMiddleware::CSRF_TOKEN_HEADER)) => 'unknown',
            ],
        );

        $request->session->set(CsrfMiddleware::CSRF_TOKEN_NAME, $token);
        self::assertSame('success', $middleware->handle($request, static fn() => 'success'));
    }

    public function testHandlePostNoToken(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware();
        self::expectException(CsrfMismatchException::class);
        self::expectExceptionMessage('CSRF token mismatch');

        $middleware->handle($request, static fn() => null);
    }

    public function testHandlePostNoTokenHttpStatus(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware();

        try {
            $middleware->handle($request, static fn() => null);
        } catch (CsrfMismatchException $e) {
            self::assertSame(ResponseStatus::FORBIDDEN, $e->getResponseStatus());
        }
    }
}
