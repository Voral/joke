<?php

namespace Vasoft\Joke\Core\Collections;

use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Core\Exceptions\JokeException;

/**
 * Коллекция для хранения данными в виде ключ-значение. Только для чтения.
 *
 * Поддерживает значения любого скалярного типа, массивы и null.
 */
class ReadonlyPropsCollection
{
    /**
     * @param array<string,mixed> $props Начальный набор свойств
     */
    public function __construct(protected array $props) { }

    /**
     * Возвращает значение свойства по ключу.
     *
     * @param string $key Имя свойства
     * @param mixed $default Значение по умолчанию, если ключ не существует
     * @return null|int|float|string|bool|array Значение свойства или значение по умолчанию
     */
    public function get(string $key, mixed $default = null): null|int|float|string|bool|array
    {
        return $this->props[$key] ?? $default;
    }

    /**
     * Возвращает все свойства в виде ассоциативного массива.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->props;
    }

    /**
     * Проверяет существует ли заданный параметр в коллекции
     * @param string $key Имя параметра
     * @return bool true, если существует
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->props);
    }

    /**
     * Возвращает значение свойства по ключу, если не существует - выбрасывает исключение
     *
     * Можно переопределить исключение по умолчанию передав фабрику, которая принимает строковый
     * параметр "имя параметра" и возвращает исключение унаследованное от JokeException
     * @param string $key Имя параметра
     * @param (callable(string): JokeException)|null $exceptionFactory фабрика исключения
     * @return null|int|float|string|bool|array
     * @throws JokeException
     */
    public function getOrFail(string $key, ?callable $exceptionFactory = null): null|int|float|string|bool|array
    {
        if (!$this->has($key)) {
            $factory = $exceptionFactory ?? fn(string $key): JokeException => new ConfigException(
                'Property "' . $key . '" does not exist.'
            );
            throw $factory($key);
        }
        return $this->props[$key];
    }
}