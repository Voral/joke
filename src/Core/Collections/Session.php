<?php

namespace Vasoft\Joke\Core\Collections;

use Vasoft\Joke\Core\Exceptions\SessionException;

/**
 * Хранилище переменных сессии.
 */
class Session extends PropsCollection
{

    private array $unsets = [];
    private bool $modified = false;

    /**
     * Загрузка переменных сессии из $_SESSION
     * @return $this
     * @throws SessionException Исключение выбрасывается в неблокирующем режиме сессии
     */
    public function load(): static
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new SessionException();
        }
        $this->reset($_SESSION);
        $this->modified = false;
        return $this;
    }

    /**
     * Сохранение сессии
     * @return $this
     * @throws SessionException Исключение выбрасывается в неблокирующем режиме сессии
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
        };
        return $this;
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function set(string $key, mixed $value): static
    {
        $this->modified = true;
        if (array_key_exists($key, $this->unsets)) {
            unset($this->unsets[$key]);
        }
        return parent::set($key, $value);
    }

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

    public function unset(string $key): static
    {
        $this->modified = true;
        $this->unsets[$key] = true;
        return parent::unset($key);
    }
}