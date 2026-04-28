<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware;

use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Logging\LoggerInterface;
use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\JsonResponse;
use Vasoft\Joke\Http\Response\Response;
use Vasoft\Joke\Http\Response\ResponseBuilder;
use Vasoft\Joke\Http\Response\ResponseStatus;
use Vasoft\Joke\Container\Exceptions\ContainerException;

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
 * Мiddleware передает текст исключения непосредственно в тело ответа. Фильтрация чувствительных данных и
 * адаптация сообщений для конечного пользователя являются ответственностью разработчика приложения.
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
     * @throws ContainerException
     * @throws ParameterResolveException
     */
    public function handle(HttpRequest $request, callable $next): mixed
    {
        try {
            $response = $next();
        } catch (JokeException $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->container->get(LoggerInterface::class);
            $logger->error($exception);

            return $this->buildResponse($exception->getMessage())
                ->setStatus($exception->getResponseStatus());
        } catch (\Throwable $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->container->get(LoggerInterface::class);
            $logger->error($exception);

            return $this->buildResponse($exception->getMessage())
                ->setStatus(ResponseStatus::INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    /**
     * Формирует ответ с ошибкой с установленным по умолчанию типом
     *
     * @param string $message Тест сообщения
     *
     * @throws ParameterResolveException
     * @throws ContainerException
     */
    private function buildResponse(string $message): Response
    {
        $responseBuilder = $this->container->get(ResponseBuilder::class);
        $response = $responseBuilder->makeDefault();
        if ($response instanceof JsonResponse) {
            return $response->setBody(['message' => $message]);
        }

        return $response->setBody($message);
    }
}
