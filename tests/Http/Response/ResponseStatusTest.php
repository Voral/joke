<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Http\Response;

use PHPUnit\Framework\Attributes\DataProvider;
use Vasoft\Joke\Http\Response\ResponseStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Http\Response\ResponseStatus
 */
final class ResponseStatusTest extends TestCase
{
    #[DataProvider('provideHttpCases')]
    public function testHttp(ResponseStatus $status, int $code, string $message): void
    {
        self::assertSame($code, $status->value);
        self::assertSame($message, $status->http());
    }

    public static function provideHttpCases(): iterable
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
