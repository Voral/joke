<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware;

use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Http\Response\ResponseStatus;

/**
 * Перехватывает необработанные исключения и преобразует их в корректные HTTP-ответы.
 *
 * Обеспечивает, что пользователь никогда не увидит «голый» PHP-фатал. Все пойманные исключения записываются в лог
 * через сервис `'logger'`.
 *
 * - Исключения типа {@see JokeException} преобразуются в JSON-ответ
 *   с кодом статуса, заданным в самом исключении.
 * - Все остальные {@see \Exception} (и наследники) возвращают
 *   ответ 500 Internal Server Error.
 *
 * @see MiddlewareInterface
 */
class ExceptionMiddleware implements MiddlewareInterface
{
    /**
     * @param ServiceContainer $container DI-контейнер для получения логгера и других сервисов
     */
    public function __construct(private readonly ServiceContainer $container) {}

    /**
     * {@inheritDoc}
     *
     * Оборачивает вызов следующего middleware или контроллера в try-catch блок.
     * В случае исключения — возвращает JSON-ответ с ошибкой и записывает событие в лог.
     *
     * @param HttpRequest $request Текущий HTTP-запрос
     * @param callable    $next    Следующий middleware или обработчик маршрута
     *
     * @return mixed Ответ от следующего middleware или сгенерированный JSON-ответ при ошибке
     *
     * @throws ParameterResolveException
     */
    public function handle(HttpRequest $request, callable $next): mixed
    {
        try {
            $response = $next();
        } catch (JokeException $exception) {
            $this->container->get('logger')->error($exception);

            return new JsonResponse()
                ->setBody(['message' => $exception->getMessage()])
                ->setStatus($exception->getResponseStatus());
        } catch (\Exception $exception) {
            $this->container->get('logger')->error($exception);

            return new JsonResponse()
                ->setBody(['message' => $exception->getMessage()])
                ->setStatus(ResponseStatus::INTERNAL_SERVER_ERROR);
        }

        return $response;
    }
}
