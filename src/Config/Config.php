<?php

namespace Vasoft\Joke\Config;

use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Core\Exceptions\JokeException;

/**
 * Контейнер конфигурации приложения.
 *
 * Поддерживает точечную нотацию ('database.connections.mysql') и ленивую загрузку
 * конфигураций через ConfigLoader. Первая составляющая - имя конфигурации, далее переменная конфигурации
 *
 * Допустимые типы значений: null, int, float, string, bool, array.
 */
class Config
{
    /**
     * Хранилище переменных конфигурации
     * @var array<string,array>
     */
    private array $props = [];
    /**
     * @var bool Флаг загрузки основных конфигов
     */
    private bool $loaded = false;
    /**
     * Массив конфигов загрузка которых завершилась неудачей
     * @var array<string,true>
     */
    private array $missingConfigs = [];

    /**
     * @param ConfigLoader $loader Загрузчик конфигурационных файлов
     */
    public function __construct(private readonly ConfigLoader $loader)
    {
    }

    /**
     * Получает значение конфигурации по ключу.
     *
     * Поддерживает точечную нотацию (например, 'database.connections.mysql').
     * Если значение не найдено, возвращается значение по умолчанию.
     *
     * @param string $key Ключ конфигурации в формате 'config_name.property.subproperty'
     * @param null|int|float|string|bool|array $default Значение по умолчанию, если ключ не найден
     *
     * @return null|int|float|string|bool|array Значение конфигурации или значение по умолчанию
     *
     * @throws ConfigException Если указан пустой ключ
     */
    public function get(string $key, null|int|float|string|bool|array $default = null): null|int|float|string|bool|array
    {
        [$configName, $parts] = $this->parseKey($key);
        $value = $this->props[$configName];
        if (empty($parts)) {
            return $value;
        }
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }
        return $value;
    }

    /**
     * Гарантирует загрузку указанной конфигурации.
     *
     * Если базовые конфигурации ещё не загружены — загружает их.
     * Если запрошенная конфигурация отсутствует в базовых — пытается загрузить лениво.
     * Кэширует информацию об отсутствующих конфигурациях для избежания повторных попыток загрузки.
     *
     * @param string $configName Имя конфигурации (первая часть ключа до первой точки)
     *
     * @throws ConfigException Если конфигурация не найдена ни в базовых, ни в ленивых путях
     */
    protected function ensureLoading(string $configName): void
    {
        if (isset($this->missingConfigs[$configName])) {
            throw new ConfigException("Configuration '$configName' not found");
        }
        if (!$this->loaded) {
            $this->props = $this->loader->load();
            $this->loaded = true;
        }
        if (!array_key_exists($configName, $this->props)) {
            try {
                $this->props[$configName] = $this->loader->loadLazy($configName);
            } catch (ConfigException $e) {
                $this->missingConfigs[$configName] = true;
                throw $e;
            }
        }
    }

    /**
     * Получает значение конфигурации по ключу или выбрасывает исключение, если ключ не найден.
     *
     * Поддерживает точечную нотацию (например, 'database.connections.mysql').
     * В отличие от метода get(), строго проверяет существование каждого уровня вложенности.
     *
     * @param string $key Ключ конфигурации в формате 'config_name.property.subproperty'
     * @param (callable(string): JokeException)|null $exceptionFactory Фабрика исключений. Если null, используется стандартное ConfigException.
     *                                        Должна принимать строковый ключ и возвращать JokeException.
     *
     * @return null|int|float|string|bool|array Значение конфигурации
     *
     * @throws JokeException|ConfigException Если ключ не найден (используется либо кастомное, либо стандартное исключение)
     * @throws ConfigException Если указан пустой ключ
     */

    public function getOrFail(
        string $key,
        ?callable $exceptionFactory = null
    ): null|int|float|string|bool|array {
        [$configName, $parts] = $this->parseKey($key);
        $value = $this->props[$configName];
        if (empty($parts)) {
            return $value;
        }
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                $factory = $exceptionFactory ?? static fn(string $key): JokeException => new ConfigException(
                    "Property '" . $key . "' does not exist."
                );
                throw $factory($key);
            }
        }
        return $value;
    }

    /**
     * Разбирает конфигурационный ключ на имя конфигурации и путь внутри неё.
     *
     * Метод разделяет переданный ключ по символу точки и извлекает первую часть
     * как имя конфигурационного файла. Остальные части рассматриваются как
     * путь для навигации внутри конфигурационного массива.
     *
     * Примеры:
     * - 'app' → ['app', []]
     * - 'database.connections' → ['database', ['connections']]
    … *
     * @throws ConfigException Если передан пустой ключ
     */
    private function parseKey(string $key): array
    {
        if ($key === '') {
            throw new ConfigException('Config key cannot be empty');
        }
        $parts = explode('.', $key);
        $configName = array_shift($parts);
        $this->ensureLoading($configName);
        return [$configName, $parts];
    }
}