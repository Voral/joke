<?php

declare(strict_types=1);

namespace Vasoft\Joke\Config;

use Vasoft\Joke\Config\Exceptions\ConfigException;

/**
 * Базовый абстрактный класс для всех конфигурационных объектов фреймворка.
 *
 * Обеспечивает единый механизм защиты от изменений в рантайме (immutable pattern после инициализации).
 *
 * @rule Все конфигурационные классы модулей (App, Database, Cache и т.д.)
 *       ДОЛЖНЫ наследоваться от этого класса.
 *
 * @see ConfigManager::load()
 */
abstract class AbstractConfig
{
    private bool $frozen = false;

    /**
     * Замораживает конфигурацию, запрещая дальнейшие изменения.
     */
    final public function freeze(): void
    {
        $this->frozen = true;
    }

    /**
     * Проверяет статус заморозки.
     */
    final public function isFrozen(): bool
    {
        return $this->frozen;
    }

    /**
     * Внутренний метод-гард. Вызывайте его в начале каждого мутатора (сеттера).
     *
     * @throws ConfigException если конфиг уже заморожен
     */
    final protected function guard(): void
    {
        if ($this->frozen) {
            throw new ConfigException(
                sprintf('Cannot modify frozen configuration of [%s].', static::class),
            );
        }
    }
}
