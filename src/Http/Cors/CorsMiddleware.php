<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Cors;

use Vasoft\Joke\Contract\Middleware\MiddlewareInterface;
use Vasoft\Joke\Http\HttpMethod;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Http\Response\Response;
use Vasoft\Joke\Http\Response\ResponseBuilder;
use Vasoft\Joke\Http\Response\ResponseStatus;

/**
 * Middleware для реализации механизма CORS (Cross-Origin Resource Sharing).
 *
 *  Обрабатывает кросс-доменные запросы, добавляя необходимые заголовки доступа
 *  в ответ сервера. Поддерживает обработку preflight-запросов (метод OPTIONS),
 *  валидацию источников (Origin), настройку разрешенных методов и заголовков,
 *  а также работу с учетными данными (cookies, authorization).
 *
 *  Особенности реализации:
 *  - Перехватывает OPTIONS-запросы до выполнения основной логики приложения,
 *     возвращая пустой успешный ответ с заголовками.
 *  - Динамически проверяет заголовок Origin запроса на соответствие списку разрешенных.
 *  - Добавляет заголовок Vary: Origin для корректного кэширования ответов.
 *  - Блокирует установку заголовков, если источник не прошел валидацию.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Конструктор middleware.
     *
     * @param CorsConfig      $corsConfig      конфигурация правил CORS
     * @param ResponseBuilder $responseBuilder Билдер ответов для создания и нормализации объектов Response
     */
    public function __construct(
        private readonly CorsConfig $corsConfig,
        private readonly ResponseBuilder $responseBuilder,
    ) {}

    /**
     * Обрабатывает входящий HTTP-запрос, добавляя CORS-заголовки к ответу.
     *
     * Логика работы:
     * 1. Если запрос содержит заголовок Origin и CORS активирован:
     * - Проверяет допустимость Origin
     * - Для OPTIONS-запросов обрабатывает preflight
     * - Для обычных запросов проверяет допустимость HTTP-метода
     * 2. Если все проверки пройдены:
     * - Выполняет следующую middleware в цепочке
     * - Добавляет CORS-заголовки к ответу
     * 3. Возвращает финальный HTTP-ответ
     *
     * @param HttpRequest $request объект входящего HTTP-запроса
     * @param callable    $next    следующий обработчик в цепочке middleware
     *
     * @return Response объект HTTP-ответа с установленными заголовками CORS
     * */
    public function handle(HttpRequest $request, callable $next): Response
    {
        $origin = $request->getOrigin();
        $needCors = $this->corsConfig->allowedCors && '' !== $origin;
        $isPreflight = HttpMethod::OPTIONS === $request->method;
        if ('' !== $origin && (!$this->corsConfig->allowedCors || !$this->isOriginAllowed($origin))) {
            $response = $this->responseBuilder->makeDefault();
            $response->setStatus(ResponseStatus::FORBIDDEN);

            return $response;
        }
        if ($isPreflight) {
            return $this->handlePreflight($request);
        }

        if ($needCors && !in_array($request->method, $this->corsConfig->methods, true)) {
            $response = $this->responseBuilder->makeDefault();
            $response->setStatus(ResponseStatus::METHOD_NOT_ALLOWED);

            return $response;
        }
        $response = $next($request);
        $preparedResponse = $this->responseBuilder->make($response);
        if ($needCors) {
            $this->setHeaders($request, $preparedResponse);
        }

        return $preparedResponse;
    }

    /**
     * Проверяет допустимость Origin запроса согласно конфигурации CORS.
     *
     * @param string $origin Origin входящего HTTP-запроса
     *
     * @return bool true если Origin разрешён, false в противном случае
     */
    private function isOriginAllowed(string $origin): bool
    {
        if (!$this->corsConfig->allowCredentials && in_array('*', $this->corsConfig->origins, true)) {
            return true;
        }

        return '' !== $origin && in_array($origin, $this->corsConfig->origins, true);
    }

    /**
     * Обрабатывает preflight-запрос (HTTP метод OPTIONS).
     *
     * Проверяет валидность запрошенного метода и заголовков из preflight-запроса.
     * Если проверки не пройдены, возвращает статус 403 Forbidden.
     * В случае успеха устанавливает все необходимые CORS-заголовки.
     *
     * @param HttpRequest $request объект входящего HTTP-запроса
     *
     * @return Response объект HTTP-ответа с CORS-заголовками или ошибкой
     */
    private function handlePreflight(HttpRequest $request): Response
    {
        $response = $this->responseBuilder->makeDefault();
        if ($this->isPreflightMethodInvalid($request) || $this->isPreflightHeadersInvalid($request)) {
            $response->setStatus(ResponseStatus::FORBIDDEN);

            return $response;
        }
        $this->setHeaders($request, $response);

        return $response;
    }

    /**
     * Проверяет валидность метода из preflight-запроса.
     *
     * Метод считается невалидным если:
     * - Заголовок Access-Control-Request-Method отсутствует или пустой
     * - Значение не является валидным HTTP-методом
     * - Метод не входит в список разрешённых в конфигурации CORS
     *
     * @param HttpRequest $request объект входящего HTTP-запроса
     *
     * @return bool true если метод невалиден, false если валиден
     */
    private function isPreflightMethodInvalid(HttpRequest $request): bool
    {
        $requestedMethod = $request->headers->get('Access-Control-Request-Method', '');
        if ('' === $requestedMethod) {
            return true;
        }

        try {
            $methodEnum = HttpMethod::from($requestedMethod);

            return !in_array($methodEnum, $this->corsConfig->methods, true);
        } catch (\ValueError $exception) {
            return true;
        }
    }

    /**
     * Проверяет валидность заголовков из preflight-запроса.
     *
     * Заголовки считаются невалидными если:
     * - Заголовок Access-Control-Request-Headers содержит хотя бы один недопустимый заголовок
     *
     * Отсутствие заголовка Access-Control-Request-Headers считается валидным случаем.
     *
     * @param HttpRequest $request объект входящего HTTP-запроса
     *
     * @return bool true если заголовки невалидны, false если валидны
     */
    private function isPreflightHeadersInvalid(HttpRequest $request): bool
    {
        $requestedHeaders = $request->headers->get('Access-Control-Request-Headers', '');
        if ('' === $requestedHeaders) {
            return false;
        }
        $requestedHeadersArray = array_map('trim', explode(',', strtolower($requestedHeaders)));
        $allowedHeadersArray = array_map('strtolower', $this->corsConfig->headers);
        foreach ($requestedHeadersArray as $header) {
            if (!in_array($header, $allowedHeadersArray, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Устанавливает все необходимые CORS-заголовки в объект ответа.
     *
     * Включает заголовки:
     * - Access-Control-Allow-Origin и Access-Control-Allow-Credentials
     * - Access-Control-Allow-Methods
     * - Access-Control-Allow-Headers
     * - Access-Control-Expose-Headers
     * - Access-Control-Max-Age
     *
     * @param HttpRequest $request  объект входящего HTTP-запроса
     * @param Response    $response объект ответа, в который будут установлены заголовки
     */
    private function setHeaders(HttpRequest $request, Response $response): void
    {
        $this->setOriginAndCredentials($request, $response);
        $response->headers
            ->set('Access-Control-Allow-Methods', $this->corsConfig->getMethodsAsString())
            ->set('Access-Control-Allow-Headers', $this->corsConfig->getHeadersAsString())
            ->set('Access-Control-Expose-Headers', $this->corsConfig->getExposeHeadersAsString())
            ->set('Access-Control-Max-Age', (string) $this->corsConfig->maxAge);
    }

    /**
     * Устанавливает заголовки Access-Control-Allow-Origin и Access-Control-Allow-Credentials.
     *
     * Логика валидации источника (Origin):
     * 1. Если включен режим без учетных данных и разрешен - устанавливает Origin в '*'.
     * 2. Если передан конкретный заголовок Origin и он есть в списке разрешенных:
     *    - Устанавливает Origin в значение из запроса.
     *    - Если включены учетные данные, устанавливает Allow-Credentials: true.
     *    - Добавляет заголовок Vary: Origin для корректной работы кэша.
     *
     * @param HttpRequest $request  объект запроса для чтения заголовка HTTP_ORIGIN
     * @param Response    $response объект ответа для установки заголовков
     */
    private function setOriginAndCredentials(HttpRequest $request, Response $response): void
    {
        if (!$this->corsConfig->allowCredentials && in_array('*', $this->corsConfig->origins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', '*');

            return;
        }
        $origin = $request->getOrigin();
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        if ($this->corsConfig->allowCredentials) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        $response->headers->set('Vary', 'Origin');
    }
}
