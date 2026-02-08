<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Core\Request;

use Vasoft\Joke\Core\Request\Exceptions\WrongRequestMethodException;
use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Response\ResponseStatus;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\Request\HttpRequest
 */
final class HttpRequestTest extends TestCase
{
    public function testFromGlobals(): void
    {
        $_GET = ['getVariable' => 'getValue'];
        $_POST = ['postVariable' => 'postValue'];
        $_SERVER = ['serverVariable' => 'serverValue', 'HTTP_HEADER_VARIABLE' => 'headerValue'];
        $_COOKIE = ['cookieVariable' => 'cookieValue'];
        $_FILES = [
            [
                'name' => 'fileName',
                'type' => 'fileType',
                'tmp_name' => 'tmpName',
                'error' => 0,
                'size' => 1234,
            ],
        ];
        $request = HttpRequest::fromGlobals();
        self::assertSame('getValue', $request->get->get('getVariable'));
        self::assertSame('postValue', $request->post->get('postVariable'));
        self::assertSame('serverValue', $request->server->get('serverVariable'));
        self::assertSame('headerValue', $request->headers->get('Header-Variable'));
        self::assertSame('cookieValue', $request->cookies->get('cookieVariable'));
    }

    public function testGetMethodDefault(): void
    {
        $request = HttpRequest::fromGlobals();
        self::assertSame(HttpMethod::GET, $request->method);
    }

    public function testGetMethod(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'PUT']);
        self::assertSame(HttpMethod::PUT, $request->method);
    }

    public function testGetMethodUnknown(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'wrong']);
        self::expectException(WrongRequestMethodException::class);
        self::expectExceptionMessage('Wrong request method: WRONG');
        $test = $request->method;
    }

    public function testGetMethodUnknownResponseStatus(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'wrong']);

        try {
            $test = $request->method;
        } catch (WrongRequestMethodException $e) {
            self::assertSame(ResponseStatus::METHOD_NOT_ALLOWED, $e->getResponseStatus());
        }
    }

    public function testResetProps(): void
    {
        $request = new HttpRequest();
        self::assertEmpty($request->props->getAll());
        $newData = ['string' => 'someTest', 'int' => 2];
        $request->setProps($newData);
        self::assertSame($newData, $request->props->getAll());
    }

    public function testGetUri(): void
    {
        $request = new HttpRequest(server: ['REQUEST_URI' => 'some/uri']);
        self::assertSame('some/uri', $request->getPath());
    }

    public function testGetUriDefault(): void
    {
        $request = new HttpRequest();
        self::assertSame('/', $request->getPath());
    }

    public function testJson(): void
    {
        $expect = ['example' => 1, 'stringValue' => 'someValue', 'boolValue' => true];
        $request = new HttpRequest(
            server: ['REQUEST_URI' => 'some/uri', 'CONTENT_TYPE' => 'application/json'],
            rawBody: json_encode($expect),
        );
        self::assertSame($expect, $request->json);
    }

    public function testUrlencoded(): void
    {
        $request = new HttpRequest(
            server: ['REQUEST_URI' => 'some/uri', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            rawBody: 'name=Alex&age=30',
        );
        self::assertSame(['name' => 'Alex', 'age' => '30'], $request->post->getAll());
    }
}
