<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Cors;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Http\HttpMethod;

class CorsConfig extends AbstractConfig
{
    /**
     * Внутренний флаг защиты от рекурсивного вызова метода guardCombinations().
     */
    private bool $guardLock = false;

    /**
     * Флаг активации функциональности CORS.
     *
     * Если false, middleware пропускает запросы без добавления заголовков.
     */
    public private(set) bool $allowedCors = false {
        get => $this->allowedCors;
    }


    /**
     * Список разрешенных источников (Origin), которым доступен доступ к ресурсам.
     *
     * По умолчанию разрешены все домены ('*').
     *
     * @var list<string>
     */
    public private(set) array $origins = ['*'] {
        get {
            $this->guardCombinations();

            return $this->origins;
        }
    }

    /**
     * Список разрешенных HTTP-методов для кросс-доменных запросов.
     *
     * Access-Control-Allow-Methods
     *
     * @var list<HttpMethod>
     */
    public private(set) array $methods = [
        HttpMethod::GET,
        HttpMethod::POST,
        HttpMethod::PUT,
        HttpMethod::PATCH,
        HttpMethod::DELETE,
        HttpMethod::OPTIONS,
    ] {
        get => $this->methods;
    }

    /**
     * Список разрешенных пользовательских заголовков запроса.
     *
     * Access-Control-Allow-Headers
     *
     * @var list<string>
     */
    public private(set) array $headers = ['Content-Type', 'Authorization', 'X-Requested-With'] {
        get => $this->headers;
    }

    /**
     * Список заголовков ответа, которые браузеру разрешено читать клиентскому скрипту.
     *
     * Access-Control-Expose-Headers
     *
     * @var list<string>
     */
    public private(set) array $exposeHeaders = [] {
        get => $this->exposeHeaders;
    }

    /**
     * Время жизни (в секундах) результата preflight-запроса в кэше браузера.
     *
     * Access-Control-Max-Age
     */
    public private(set) int $maxAge = 3600 {
        get => $this->maxAge;
    }

    /**
     * Флаг разрешения отправки учетных данных (куки, заголовки авторизации) в кросс-доменных запросах.
     *
     * Access-Control-Allow-Credentials
     *
     * Не может быть использован совместно с * в списке origins.
     */
    public private(set) bool $allowCredentials = false {
        get {
            $this->guardCombinations();

            return $this->allowCredentials;
        }
    }

    /**
     * Устанавливает список разрешенных источников (Origin).
     *
     * @param array<string> $origins список доменов или символ '*'
     *
     * @throws ConfigException Если конфигурация в режиме только для чтения
     */
    public function setOrigins(array $origins): static
    {
        $this->guard();
        $this->origins = $origins;

        return $this;
    }

    /**
     * Активирует или деактивирует всю функциональность CORS.
     *
     * @param bool $allowedCors флаг активности
     *
     * @throws ConfigException Если конфигурация в режиме только для чтения
     */
    public function setAllowedCors(bool $allowedCors): static
    {
        $this->guard();
        $this->allowedCors = $allowedCors;

        return $this;
    }

    /**
     * Устанавливает список разрешенных HTTP-методов.
     *
     * Обеспечивает наличие в разрешенных методах OPTIONS
     *
     * @param array<HttpMethod> $methods массив экземпляров enum HttpMethod
     *
     * @throws ConfigException Если конфигурация в режиме только для чтения
     */
    public function setMethods(array $methods): static
    {
        $this->guard();
        if (!in_array(HttpMethod::OPTIONS, $methods, true)) {
            $methods[] = HttpMethod::OPTIONS;
        }

        $this->methods = $methods;

        return $this;
    }

    /**
     * Устанавливает список разрешенных заголовков запроса.
     *
     * @param array<string> $headers список имен заголовков
     *
     * @throws ConfigException Если конфигурация в режиме только для чтения
     */
    public function setHeaders(array $headers): static
    {
        $this->guard();

        $this->headers = array_map('trim', $headers);

        return $this;
    }

    /**
     * Устанавливает список заголовков ответа, доступных для чтения клиентом.
     *
     * @param array<string> $exposeHeaders список имен заголовков
     *
     * @throws ConfigException Если конфигурация в режиме только для чтения
     */
    public function setExposeHeaders(array $exposeHeaders): static
    {
        $this->guard();
        $this->exposeHeaders = array_map('trim', $exposeHeaders);

        return $this;
    }

    /**
     * Устанавливает время кеширования preflight-запроса в секундах.
     *
     * @param int $maxAge время в секундах
     *
     * @throws ConfigException Если конфигурация в режиме только для чтения
     */
    public function setMaxAge(int $maxAge): static
    {
        $this->guard();
        $this->maxAge = $maxAge;

        return $this;
    }

    /**
     * Разрешает или запрещает отправку учетных данных (куки, авторизация).
     *
     * @param bool $allowCredentials флаг разрешения
     *
     * @throws ConfigException Если конфигурация в режиме только для чтения
     */
    public function setAllowCredentials(bool $allowCredentials): static
    {
        $this->guard();
        $this->allowCredentials = $allowCredentials;

        return $this;
    }

    /**
     * Возвращает строку разрешенных HTTP-методов, разделенных запятыми.
     */
    public function getMethodsAsString(): string
    {
        return implode(',', array_map(static fn(HttpMethod $method) => $method->name, $this->methods));
    }

    /**
     * Возвращает строку разрешенных заголовков запроса, разделенных запятыми.
     */
    public function getHeadersAsString(): string
    {
        return implode(',', $this->headers);
    }

    /**
     * Возвращает строку разрешенных заголовков ответа, разделенных запятыми.
     */
    public function getExposeHeadersAsString(): string
    {
        return implode(',', $this->exposeHeaders);
    }

    /**
     * Выполняет валидацию несовместимых комбинаций настроек CORS.
     *
     * Проверяет запрет спецификации CORS на одновременное использование
     * wildcard ('*') в origins и включенного флага allowCredentials.
     *
     * Использует механизм блокировки (guardLock) для предотвращения бесконечной рекурсии
     * при взаимном обращении геттеров свойств.
     *
     * @throws ConfigException если обнаружена недопустимая комбинация настроек после заморозки конфига
     */
    private function guardCombinations(): void
    {
        if ($this->guardLock) {
            return;
        }
        $this->guardLock = true;
        if ($this->isFrozen() && $this->allowCredentials && $this->allowedCors && in_array('*', $this->origins, true)) {
            $this->guardLock = false;

            throw new ConfigException(
                'Cannot use wildcard origin when allowCredentials is enabled. Please specify explicit domains.',
            );
        }
        $this->guardLock = false;
    }
}
