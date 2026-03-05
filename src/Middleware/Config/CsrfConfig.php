<?php

declare(strict_types=1);

namespace Vasoft\Joke\Middleware\Config;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Http\Cookies\CookieConfig;
use Vasoft\Joke\Middleware\Config\Enums\CsrfTransportMode;

/**
 * Конфигурация механизма CSRF токена.
 */
class CsrfConfig extends AbstractConfig
{
    /** Режим отправки токена пользователю */
    public private(set) CsrfTransportMode $transportMode = CsrfTransportMode::HEADER {
        get {
            return $this->transportMode;
        }
    }
    /**
     * Настройки для куки CSRF токена.
     */
    public private(set) CookieConfig $cookieConfig {
        get {
            return $this->cookieConfig ??= new CookieConfig()->freeze();
        }
    }

    /**
     * Задает режим отправки токена пользователю.
     *
     * @return $this
     *
     * @throws ConfigException При попытке задать значение когда конфигурация в режим для чтения
     */
    public function setTransportMode(CsrfTransportMode $transportMode): static
    {
        $this->guard();
        $this->transportMode = $transportMode;

        return $this;
    }

    /**
     * Задает параметры куки CSRF токена.
     */
    public function setCookieConfig(CookieConfig $cookieConfig): static
    {
        $this->guard();
        $this->cookieConfig = $cookieConfig;

        return $this;
    }

    public function freeze(): static
    {
        $this->cookieConfig->freeze();

        return parent::freeze();
    }
}
