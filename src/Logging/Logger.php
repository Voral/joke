<?php

declare(strict_types=1);

namespace Vasoft\Joke\Logging;

use Vasoft\Joke\Contract\Logging\LoggerInterface;
use Vasoft\Joke\Contract\Logging\LogHandlerInterface;
use Vasoft\Joke\Contract\Logging\MessageFormatterInterface;
use Vasoft\Joke\Logging\Exception\LogException;

/**
 * Типизированный логгер с поддержкой нескольких обработчиков.
 *
 * Реализует внутренний контракт логирования (не PSR-3), используя строгую типизацию и enum {@see LogLevel}
 * для уровней серьёзности.
 *
 * Отличия от PSR-3:
 * - Метод {@see log()} принимает уровень как объект {@see LogLevel}, а не строку.
 * - Сообщение может быть строкой или объектом с методом __toString().
 *
 * Интерполяция сообщения выполняется заданным форматировщиком. Исходное сообщение сохраняется в контексте
 * под ключом `'rawMessage'` для возможной кастомной обработки в handler’ах.
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    /**
     * @param list<LogHandlerInterface> $handlers
     *
     * @throws LogException
     */
    public function __construct(
        private readonly array $handlers,
        private readonly MessageFormatterInterface $formatter = new DefaultMessageFormatter(3),
    ) {
        if (empty($this->handlers)) {
            throw new LogException('At least one log handler must be provided.');
        }
    }

    /**
     * {@inheritDoc}
     *
     * Выполняет интерполяцию на основе `$context`. Готовое сообщение и контекст (с добавленным `'rawMessage'`)
     * передаются всем зарегистрированным обработчикам.
     */
    public function log(LogLevel $level, object|string $message, array $context = []): void
    {
        $interpolatedMessage = $this->formatter->interpolate($message, $context);
        if (!isset($context['rawMessage'])) {
            $context['rawMessage'] = $message;
        }
        foreach ($this->handlers as $handler) {
            $handler->write($level, $interpolatedMessage, $context);
        }
    }
}
