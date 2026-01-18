<?php

namespace Vasoft\Joke\Tests\Core\Response;

use phpmock\phpunit\MockObjectProxy;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\Collections\HeadersCollection;
use Vasoft\Joke\Core\Response\HtmlResponse;
use Vasoft\Joke\Core\Response\ResponseStatus;

class ResponseTest extends TestCase
{
    use PHPMock;

    private MockObjectProxy|MockObject $headerMock;

    protected function setUp(): void
    {
        $this->headerMock = $this->getFunctionMock('Vasoft\Joke\Core\Response', 'header');
    }

    public function testDefaultStatusIsOk(): void
    {
        $response = new HtmlResponse();
        $this->assertEquals(ResponseStatus::OK, $response->status);
    }

    public function testSetStatus(): void
    {
        $response = new HtmlResponse();
        $response->status = ResponseStatus::NOT_FOUND;
        $this->assertEquals(ResponseStatus::NOT_FOUND, $response->status);
    }

    public function testHeadersCollectionIsInitialized(): void
    {
        $response = new HtmlResponse();
        $this->assertInstanceOf(HeadersCollection::class, $response->headers);
        $this->assertEquals(['Content-Type' => 'text/html'], $response->headers->getAll());
    }

    public function testAddHeaderViaCollection(): void
    {
        $response = new HtmlResponse();
        $response->headers->set('Content-Type', 'application/json');

        $this->assertEquals(['Content-Type' => 'application/json'], $response->headers->getAll());
    }

    #[RunInSeparateProcess]
    public function testSendCallsHeaderAndEchoesBody(): void
    {
        $response = new HtmlResponse();
        $response->headers->set('X-Custom', 'test-value');
        $response->setBody('Hello, world!');

        $headerParams = [];
        $this->headerMock->expects($this->exactly(2))
            ->willReturnCallback(function ($header) use (&$headerParams) {
                $headerParams[] = $header;
            });
        ob_start();
        $response->send();
        $output = ob_get_clean();

        $expectedHeaders = [
            'Content-Type: text/html',
            'X-Custom: test-value'
        ];

        sort($headerParams);
        sort($expectedHeaders);

        $this->assertEquals($expectedHeaders, $headerParams);

        $this->assertSame('Hello, world!', $output);
    }

    public function testSendReturnsSelf(): void
    {
        $response = new HtmlResponse();
        $returned = $response->send();
        $this->assertSame($response, $returned);
    }
}
