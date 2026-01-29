<?php

namespace Vasoft\Joke\Config;

use Vasoft\Joke\Config\Exceptions\ConfigException;

/**
 * Клас для определения текущего окружения и доступа к переменным окружения
 *
 * Определять текущее окружение из источников (в порядке приоритета)
 * - $_ENV['JK_ENV']
 * - $_SERVER['JK_ENV']
 * - getenv('JK_ENV')
 *
 * Если не задано, то окружение считается local
 * Загружает переменные окружения:
 * - .env
 * - .env.{env}
 * - .env.local (загружается один раз и имеет самый высокий приоритет)
 */
class Environment
{
    /**
     * Имя переменной окружения, используется для определения текущего режима
     */
    public const string ENV_VAR_NAME = 'JK_ENV';
    /**
     * Стандартное имя для production окружения
     */
    public const string ENV_PRODUCTION = 'production';
    /**
     * Стандартное имя для окружения разработки
     */
    public const string ENV_DEVELOPMENT = 'development';
    /**
     * Стандартное имя для тестового окружения
     */
    public const string ENV_TESTING = 'testing';
    /**
     * Стандартное имя для локального окружения
     */
    public const string ENV_LOCAL = 'local';
    /**
     * Ассоциативный массив переменных окружения
     * @var array<string,float|int|string|null|bool>
     */
    private array $vars = [];

    public string $name {
        get => $this->name;
    }

    public function __construct(EnvironmentLoader $loader)
    {
        $environmentName = $_ENV[self::ENV_VAR_NAME]
            ?? $_SERVER[self::ENV_VAR_NAME]
            ?? getenv(self::ENV_VAR_NAME)
            ?: null;
        $this->name = is_string($environmentName) ? $environmentName : self::ENV_LOCAL;
        $this->vars = $loader->load($this->name, self::ENV_LOCAL, self::ENV_TESTING);
    }

    /**
     * Проверяет, совпадает ли запрошенное окружение с текущим
     * @param string $name Запрошенное окружение
     * @return bool true, если совпадает
     */
    public function is(string $name): bool
    {
        return $name === $this->name;
    }

    /**
     * Проверяет, является ли текущее окружение production
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->name === self::ENV_PRODUCTION;
    }

    /**
     * Проверяет, является ли текущее окружение development
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return $this->name === self::ENV_DEVELOPMENT;
    }

    /**
     * Проверяет, является ли текущее окружение testing
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->name === self::ENV_TESTING;
    }

    /**
     * Возвращает значение переменной окружения или значение по умолчанию
     *
     * Имя переменной нечувствительно к регистру
     *
     * @param string $name Имя переменной
     * @param int|float|string|bool|null $defaultValue Значение по умолчанию
     * @return int|float|string|bool|null
     */
    public function get(string $name, int|float|string|bool|null $defaultValue = null): int|float|string|bool|null
    {
        return $this->vars[strtoupper($name)] ?? $defaultValue;
    }

    /**
     * Проверяет, существует ли переменная окружения
     *
     * Имя переменной нечувствительно к регистру
     * @param string $name Имя переменной
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists(strtoupper($name), $this->vars);
    }

    /**
     * Возвращает значение переменной или выбрасывает исключение, если ее нет
     * @param string $name Имя переменной
     * @param string|null $message Сообщение об ошибке или null - для сообщения по умолчанию
     * @return int|float|string|bool|null
     * @throws ConfigException если переменная не существует в окружении
     */
    public function getOrFail(string $name, ?string $message = null): int|float|string|bool|null
    {
        if (!$this->has($name)) {
            $message = $message ?? ('The environment "' . strtoupper($name) . '" does not exist.');
            throw new ConfigException($message);
        }
        return $this->get($name);
    }

}