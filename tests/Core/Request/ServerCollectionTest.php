<?php

namespace Vasoft\Joke\Tests\Core\Request;

use Vasoft\Joke\Core\Request\ServerCollection;
use PHPUnit\Framework\TestCase;

class ServerCollectionTest extends TestCase
{
    public function testParseHeaderFromServer(): void
    {
        $agent = 'Mozilla/5.0 ' . date('Y-m-d H:i:s');
        $serverCollection = new ServerCollection([
            'HTTP_USER_AGENT' => $agent,
            'HTTP_CUSTOM_HEADER' => 'My Custom Header',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => '100',
            'CONTENT_ENCODING' => 'gzip',
            'CONTENT_LANGUAGE' => 'en-US',
            'CONTENT_MD5' => 'QWxhZGRpbWVudA==',
        ]);
        $headers = $serverCollection->getHeaders();
        $this->assertEquals($agent, $headers['User-Agent'] ?? null);
        $this->assertEquals('My Custom Header', $headers['Custom-Header'] ?? null);
        $this->assertEquals('application/json', $headers['Content-Type'] ?? null);
        $this->assertEquals('100', $headers['Content-Length'] ?? null);
        $this->assertEquals('gzip', $headers['Content-Encoding'] ?? null);
        $this->assertEquals('en-US', $headers['Content-Language'] ?? null);
        $this->assertEquals('QWxhZGRpbWVudA==', $headers['Content-MD5'] ?? null);
    }
    public function testParseHeaderFromServerDefaultValues(): void
    {
        $serverCollection = new ServerCollection([]);
        $headers = $serverCollection->getHeaders();
        $this->assertEquals('text/html', $headers['Content-Type'] ?? null);
        $this->assertEquals('0', $headers['Content-Length'] ?? null);
        $this->assertEquals('', $headers['Content-Encoding'] ?? null);
        $this->assertEquals('', $headers['Content-Language'] ?? null);
        $this->assertEquals('', $headers['Content-MD5'] ?? null);
    }
}
