<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Fixtures\Middlewares;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\HtmlResponse;

class SingleMiddleware implements MiddlewareInterface
{
    private static array $begin = [];
    private static array $end = [];
    public int $index = 0;
    public static int $firstIndex = 0;

    public function handle(HttpRequest $request, callable $next): HtmlResponse
    {
        self::$begin[] = sprintf('Middleware %d begin', $this->index);
        /** @var HtmlResponse $response */
        $response = $next();
        self::$end[] = sprintf('Middleware %d end', $this->index);
        if (self::$firstIndex === $this->index) {
            $answer = array_merge(self::$begin, [$response->getBody()], self::$end);

            $body = implode('#', $answer);
            $response->setBody($body);
        }

        return $response;
    }

    public static function clean(): void
    {
        self::$begin = [];
        self::$end = [];
    }
}
