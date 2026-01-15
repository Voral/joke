<?php

namespace Vasoft\Joke\Tests\Core\Request;

use Vasoft\Joke\Core\Request\Exceptions\WrongRequestMethodException;
use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Response\ResponseStatus;

class HttpRequestTest extends TestCase
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
                'size' => 1234
            ]
        ];
        $request = HttpRequest::fromGlobals();
        self::assertEquals('getValue', $request->get->get('getVariable'));
        self::assertEquals('postValue', $request->post->get('postVariable'));
        self::assertEquals('serverValue', $request->server->get('serverVariable'));
        self::assertEquals('headerValue', $request->headers->get('Header-Variable'));
        self::assertEquals('cookieValue', $request->cookies->get('cookieVariable'));
    }

    public function testGetMethodDefault(): void
    {
        $request = HttpRequest::fromGlobals();
        self::assertEquals(HttpMethod::GET, $request->method);
    }

    public function testGetMethod(): void
    {
        $request = new HttpRequest(server: ['REQUEST_METHOD' => 'PUT']);
        self::assertEquals(HttpMethod::PUT, $request->method);
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
            self::assertEquals(ResponseStatus::METHOD_NOT_ALLOWED, $e->getResponseStatus());
        }
    }
}
