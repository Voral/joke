<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Logging;

/**
 * Интерфейс для форматирования (интерполяции) лог-сообщений.
 *
 * Отвечает за преобразование исходного сообщения (строки или объекта) и контекста
 * в готовую строку, пригодную для записи обработчиками.
 *
 * Обычно заменяет плейсхолдеры вида `{key}` на значения из `$context`,
 * но может реализовывать любую другую стратегию форматирования.
 */
interface MessageFormatterInterface
{
    /**
     * Преобразует сообщение и контекст в готовую строку.
     *
     * @param object|string $message Исходное сообщение: строка, объект с __toString() или экземпляр \Throwable
     * @param array         $context Контекст для подстановки или обогащения
     *
     * @return string Готовое к записи сообщение
     */
    public function interpolate(object|string $message, array $context = []): string;
}
