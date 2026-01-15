<?php

namespace Vasoft\Joke\Tests\Core\Response;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Core\Response\ResponseStatus;
use PHPUnit\Framework\TestCase;

class ResponseStatusTest extends TestCase
{

    /**
     * @param ResponseStatus $status
     * @param int $code
     * @param string $message
     * @return void
     */
    #[DataProvider('dataProviderHttpStatus')]
    public function testHttp(ResponseStatus $status, int $code, string $message)
    {
        $this->assertEquals($code, $status->value);
        $this->assertEquals($message, $status->http());
    }

    public static function dataProviderHttpStatus(): array
    {
        return [
            [ResponseStatus::OK, 200, 'OK'],
            [ResponseStatus::BAD_REQUEST, 400, 'Bad Request'],
            [ResponseStatus::UNAUTHORIZED, 401, 'Unauthorized'],
            [ResponseStatus::FORBIDDEN, 403, 'Forbidden'],
            [ResponseStatus::NOT_FOUND, 404, 'Not Found'],
            [ResponseStatus::INTERNAL_SERVER_ERROR, 500, 'Internal Server Error'],
        ];
    }
}
