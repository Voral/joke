<?php

declare(strict_types=1);

namespace Vasoft\Joke\RateLimiter;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;

/**
 * Конфигурация системы Rate Limiting.
 *
 * Позволяет настраивать:
 * - Путь к файловому хранилищу
 * - Правила лимитов (именованные, с разными окнами)
 * - Класс идентификатора клиента
 *
 * Конфигурация загружается на каждый запрос, что позволяет менять настройки
 * без перезагрузки сервера.
 *
 * @see AbstractConfig
 */
class RateLimiterConfig extends AbstractConfig
{
    /**
     * Путь к директории файлового хранилища.
     */
    private string $storagePath = '';

    /**
     * Правила ограничения запросов.
     *
     * @var array<string, RuleConfig>
     */
    private array $rules = [];

    /**
     * Класс идентификатора клиента.
     *
     * @var class-string
     */
    private string $clientIdentifierClass = '';

    /**
     * Возвращает путь к директории файлового хранилища.
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Возвращает правила ограничения запросов.
     *
     * @return array<string, RuleConfig>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Возвращает класс идентификатора клиента.
     *
     * @return class-string
     */
    public function getClientIdentifierClass(): string
    {
        return $this->clientIdentifierClass;
    }

    /**
     * Устанавливает путь к директории файлового хранилища.
     *
     * @param string $path Абсолютный или относительный путь
     *
     * @return $this
     *
     * @throws ConfigException Если конфигурация заморожена
     */
    public function setStoragePath(string $path): static
    {
        $this->guard();
        $this->storagePath = $path;

        return $this;
    }

    /**
     * Устанавливает правила ограничения запросов.
     *
     * Пример формата:
     * ```php
     * [
     *     'default' => ['limit' => 100, 'window' => 60],
     *     'api'     => ['limit' => 1000, 'window' => 3600],
     *     'login'   => ['limit' => 5, 'window' => 60],
     * ]
     * ```
     *
     * @param array<string, array{limit: int, window: int}> $rules Ассоциативный массив правил
     *
     * @return $this
     *
     * @throws ConfigException Если конфигурация заморожена
     */
    public function setRules(array $rules): static
    {
        $this->guard();
        $this->rules = [];
        foreach ($rules as $name => $config) {
            $this->rules[$name] = new RuleConfig(
                limit: $config['limit'],
                windowSize: $config['window'],
                name: $name,
            );
        }

        return $this;
    }

    /**
     * Устанавливает класс идентификатора клиента.
     *
     * @param class-string $class Полное имя класса, реализующего ClientIdentifierInterface
     *
     * @return $this
     *
     * @throws ConfigException Если конфигурация заморожена
     */
    public function setClientIdentifierClass(string $class): static
    {
        $this->guard();
        $this->clientIdentifierClass = $class;

        return $this;
    }
}