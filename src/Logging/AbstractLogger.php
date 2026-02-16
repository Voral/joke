<?php

declare(strict_types=1);

namespace Vasoft\Joke\Logging;

use Vasoft\Joke\Contract\Logging\LoggerInterface;

/**
 * Базовый абстрактный класс для реализации логгеров.
 *
 * Предоставляет стандартные методы уровней логирования (emergency, alert, critical и т.д.),
 * делегируя их выполнение абстрактному методу {@see log()}.
 *
 * Наследники обязаны реализовать только метод {@see log()}, чтобы получить
 * полнофункциональный логгер, совместимый с {@see LoggerInterface}.
 */
abstract class AbstractLogger implements LoggerInterface
{
    public function emergency(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(object|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    abstract public function log(LogLevel $level, object|string $message, array $context = []): void;
}
