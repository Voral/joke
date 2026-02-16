<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Logging;

use Vasoft\Joke\Logging\LogLevel;

/**
 * Интерфейс обработчика логирования.
 *
 * Определяет контракт для компонентов, отвечающих за физическую запись или передачу лог-сообщений
 * (в файл, сеть, внешний сервис и т.д.).
 *
 * Сообщение (`$message`) уже прошло интерполяцию в логгере и не содержит плейсхолдеров вида `{key}`.
 * Контекст (`$context`) может содержать дополнительные данные, например, исключение под ключом `'exception'`.
 *
 * Обработчик сам решает, записывать ли сообщение на основе уровня серьёзности.
 */
interface LogHandlerInterface
{
    /**
     * Записывает лог-сообщение с указанным уровнем серьёзности.
     *
     * @param LogLevel $level   Уровень логирования (например, ERROR, DEBUG)
     * @param string   $message Готовое к записи сообщение (уже интерполированное)
     * @param array    $context Дополнительные данные (может содержать 'exception' => \Throwable)
     */
    public function write(LogLevel $level, string $message, array $context = []): void;
}
