<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Middleware;

use phpmock\phpunit\PHPMock;
use Vasoft\Joke\Application\ApplicationConfig;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Cookies\CookieConfig;
use Vasoft\Joke\Http\Cookies\SameSiteOption;
use Vasoft\Joke\Http\Response\ResponseBuilder;
use Vasoft\Joke\Middleware\Config\CsrfConfig;
use Vasoft\Joke\Middleware\Config\Enums\CsrfTransportMode;
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
    use PHPMock;

    private static ServiceContainer $container;

    public static function setUpBeforeClass(): void
    {
        self::$container = new ServiceContainer();
        self::$container->registerSingleton(CookieConfig::class, CookieConfig::class);
    }

    public function testHandle(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container));
        $middleware->handle($request, static fn() => null);
        self::assertNotNull($request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME));
        self::assertNotEmpty($request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME));
    }

    public function testHandlePostSuccessHeader(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container));
        $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        $request = new HttpRequest(server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/csrf',
            'HTTP_' . str_replace('-', '_', strtoupper(CsrfMiddleware::CSRF_TOKEN_HEADER)) => $token,
        ]);
        $request->session->set(CsrfMiddleware::CSRF_TOKEN_NAME, $token);
        $response = $middleware->handle($request, static fn() => 'success');

        self::assertSame('success', $response->getBody());
    }

    public function testHandlePostSuccessPost(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container));
        $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        $request = new HttpRequest(post: [CsrfMiddleware::CSRF_TOKEN_NAME => $token], server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/csrf',
            'HTTP_' . str_replace('-', '_', strtoupper(CsrfMiddleware::CSRF_TOKEN_HEADER)) => 'unknown',
        ]);
        $request->session->set(CsrfMiddleware::CSRF_TOKEN_NAME, $token);
        $response = $middleware->handle($request, static fn() => 'success');
        self::assertSame('success', $response->getBody());
    }

    public function testInjectToken(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container));
        $response = $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        self::assertSame($token, $response->headers->get('X-Csrf-Token', ''));
    }

    public function testInjectTokenCookieDefault(): void
    {
        $lifetime = 31536000;
        $fixedTime = mktime(0, 0, 0, 1, 1, 2000);
        $expectedExpires = gmdate('D, d M Y H:i:s T', $fixedTime + $lifetime);

        $timeMock = self::getFunctionMock('Vasoft\Joke\Http\Cookies', 'time');
        $timeMock->expects(self::once())->willReturn($fixedTime);

        $headers = [];
        $mockHeader = self::getFunctionMock('Vasoft\Joke\Http\Response', 'header');
        $mockHeader->expects(self::atLeastOnce())->willReturnCallback(
            static function (string $value) use (&$headers): void {
                $headers[] = $value;
            },
        );

        $config = new CsrfConfig()->setTransportMode(CsrfTransportMode::COOKIE);
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container), $config);
        $response = $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        $response->send();

        $expected = "Set-Cookie: XSRF-TOKEN={$token}; Expires={$expectedExpires}; Path=/; Secure; HttpOnly; SameSite=Lax";
        self::assertTrue(in_array($expected, $headers, true), 'The Set-Cookie header for token cookie is missing');
    }

    public function testInjectTokenCookieCustom(): void
    {
        $lifetime = 100;
        $fixedTime = mktime(0, 0, 0, 1, 1, 2000);
        $expectedExpires = gmdate('D, d M Y H:i:s T', $fixedTime + $lifetime);

        $timeMock = self::getFunctionMock('Vasoft\Joke\Http\Cookies', 'time');
        $timeMock->expects(self::once())->willReturn($fixedTime);

        $headers = [];
        $mockHeader = self::getFunctionMock('Vasoft\Joke\Http\Response', 'header');
        $mockHeader->expects(self::atLeastOnce())->willReturnCallback(
            static function (string $value) use (&$headers): void {
                $headers[] = $value;
            },
        );
        $cookieConfig = new CookieConfig()
            ->setDomain('example.com')
            ->setLifetime($lifetime)
            ->setPath('/admin')
            ->setHttpOnly(false)
            ->setSecure(false)
            ->setSameSite(SameSiteOption::Strict);
        $config = new CsrfConfig()
            ->setTransportMode(CsrfTransportMode::COOKIE)
            ->setCookieConfig($cookieConfig);
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container), $config);
        $response = $middleware->handle($request, static fn() => null);
        $token = $request->session->get(CsrfMiddleware::CSRF_TOKEN_NAME);
        $response->send();
        $expected = "Set-Cookie: XSRF-TOKEN={$token}; Expires={$expectedExpires}; Path=/admin; Domain=example.com; SameSite=Strict";
        self::assertTrue(in_array($expected, $headers, true), 'The Set-Cookie header for token cookie is missing');
    }

    public function testHandlePostSuccessGet(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container));
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
        $response = $middleware->handle($request, static fn() => 'success');
        self::assertSame('success', $response->getBody());
    }

    public function testHandlePostNoToken(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container));
        self::expectException(CsrfMismatchException::class);
        self::expectExceptionMessage('CSRF token mismatch');

        $middleware->handle($request, static fn() => null);
    }

    public function testHandlePostNoTokenHttpStatus(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/csrf']);
        $middleware = new CsrfMiddleware(new ResponseBuilder(new ApplicationConfig(), self::$container));

        try {
            $middleware->handle($request, static fn() => null);
        } catch (CsrfMismatchException $e) {
            self::assertSame(ResponseStatus::FORBIDDEN, $e->getResponseStatus());
        }
    }
}
