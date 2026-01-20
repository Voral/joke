<?php

namespace Vasoft\Joke\Core\Middlewares;

use Exception;
use Vasoft\Joke\Contract\Core\Middlewares\MiddlewareInterface;
use Vasoft\Joke\Core\Exceptions\JokeException;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\Response\JsonResponse;
use Vasoft\Joke\Core\Response\ResponseStatus;

/**
 * Перехватывает необработанные исключения и преобразует их в корректные HTTP-ответы.
 *
 * Обеспечивает, что пользователь никогда не увидит «голый» PHP-фатал.
 */
readonly class ExceptionMiddleware implements MiddlewareInterface
{
    public function handle(HttpRequest $request, callable $next): mixed
    {
        try {
            $response = $next();
        } catch (JokeException $exception) {
            return new JsonResponse()
                ->setBody(['message' => $exception->getMessage()])
                ->setStatus($exception->getResponseStatus());
        } catch (Exception $exception) {
            return new JsonResponse()
                ->setBody(['message' => $exception->getMessage()])
                ->setStatus(ResponseStatus::INTERNAL_SERVER_ERROR);
        }
        return $response;
    }
}