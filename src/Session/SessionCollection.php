<?php

declare(strict_types=1);

namespace Vasoft\Joke\Session;

use Vasoft\Joke\Collections\PropsCollection;
use Vasoft\Joke\Session\Exceptions\SessionException;

/**
 * Хранилище переменных сессии с отслеживанием изменений.
 *
 * Обеспечивает безопасную работу с $_SESSION, отслеживает модификации
 * и позволяет корректно сохранять изменения только при необходимости.
 * Используется в middleware для поддержки как блокирующего, так и неблокирующего режимов сессии.
 */
class SessionCollection extends PropsCollection
{
    /**
     * Список ключей, помеченных на удаление.
     *
     * @var array<string, bool>
     */
    private array $unsets = [];

    /**
     * Флаг, указывающий, были ли внесены изменения в данные сессии.
     */
    private bool $modified = false;

    /**
     * Загружает переменные сессии из глобального массива $_SESSION.
     *
     * @return $this
     *
     * @throws SessionException Если сессия не активна
     */
    public function load(): static
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            throw new SessionException();
        }
        $this->reset($_SESSION);
        $this->modified = false;

        return $this;
    }

    /**
     * Сохраняет изменения в глобальный массив $_SESSION.
     * Данные записываются только если были внесены изменения (флаг $modified).
     * Удаляет ключи, помеченные через метод unset().
     *
     * @return $this
     *
     * @throws SessionException Если сессия не активна
     */
    public function save(): static
    {
        if ($this->modified) {
            if (!$this->isStarted()) {
                throw new SessionException();
            }
            foreach ($this->props as $key => $prop) {
                $_SESSION[$key] = $prop;
            }
            foreach ($this->unsets as $key => $unset) {
                if (isset($_SESSION[$key])) {
                    unset($_SESSION[$key]);
                }
            }
            $this->modified = false;
        }

        return $this;
    }

    /**
     * Проверяет, активна ли сессия.
     *
     * @return bool true, если сессия запущена и активна
     */
    public function isStarted(): bool
    {
        return PHP_SESSION_ACTIVE === session_status();
    }

    /**
     * Устанавливает значение переменной сессии.
     *
     * Помечает сессию как изменённую и удаляет ключ из списка на удаление (если был добавлен).
     *
     * @param string $key   Имя переменной сессии
     * @param mixed  $value Значение (скаляр, массив или null)
     */
    public function set(string $key, mixed $value): static
    {
        $this->modified = true;
        if (array_key_exists($key, $this->unsets)) {
            unset($this->unsets[$key]);
        }

        return parent::set($key, $value);
    }

    /**
     * Полностью заменяет содержимое сессии новым набором данных.
     *
     * Помечает сессию как изменённую и очищает список ключей на удаление для всех новых ключей.
     *
     * @param array<string, mixed> $props Новый набор переменных сессии
     */
    public function reset(array $props): static
    {
        $this->modified = true;
        parent::reset($props);
        foreach ($props as $key => $value) {
            if (array_key_exists($key, $this->unsets)) {
                unset($this->unsets[$key]);
            }
        }

        return $this;
    }

    /**
     * Помечает переменную сессии на удаление.
     *
     * Переменная будет удалена из $_SESSION при вызове save().
     * Помечает сессию как изменённую.
     *
     * @param string $key Имя переменной сессии
     */
    public function unset(string $key): static
    {
        $this->modified = true;
        $this->unsets[$key] = true;

        return parent::unset($key);
    }
}
