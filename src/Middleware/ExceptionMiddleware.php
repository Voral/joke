<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Http\Response\ResponseStatus;

/**
 * Перехватывает необработанные исключения и преобразует их в корректные HTTP-ответы.
 *
 * Обеспечивает, что пользователь никогда не увидит «голый» PHP-фатал.
 */
class ExceptionMiddleware implements MiddlewareInterface
{
    public function handle(HttpRequest $request, callable $next): mixed
    {
        try {
            $response = $next();
        } catch (JokeException $exception) {
            return new JsonResponse()
                ->setBody(['message' => $exception->getMessage()])
                ->setStatus($exception->getResponseStatus());
        } catch (\Exception $exception) {
            return new JsonResponse()
                ->setBody(['message' => $exception->getMessage()])
                ->setStatus(ResponseStatus::INTERNAL_SERVER_ERROR);
        }

        return $response;
    }
}
