<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Logging;

use Vasoft\Joke\Logging\LogLevel;

/**
 * Контракт логгера.
 *
 * Сообщение ДОЛЖНО быть строкой или объектом, реализующим метод __toString().
 *
 * Сообщение МОЖЕТ содержать заполнители вида: {foo}, где foo
 * будет заменён данными из контекста по ключу "foo".
 *
 * Массив контекста может содержать произвольные данные. Единственное допущение,
 * которое могут делать реализации: если передан экземпляр Exception
 * для формирования трассировки стека, он ДОЛЖЕН находиться в ключе "exception".
 */
interface LoggerInterface
{
    /**
     * Система неработоспособна.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function emergency(object|string $message, array $context = []): void;

    /**
     * Требуется немедленное вмешательство.
     *
     * Пример: Весь сайт недоступен, база данных не отвечает и т.п.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function alert(object|string $message, array $context = []): void;

    /**
     * Критические условия.
     *
     * Пример: Компонент приложения недоступен, возникло непредвиденное исключение.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function critical(object|string $message, array $context = []): void;

    /**
     * Ошибки времени выполнения, не требующие немедленного вмешательства,
     * но которые обычно следует регистрировать и отслеживать.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function error(object|string $message, array $context = []): void;

    /**
     * Необычные ситуации, не являющиеся ошибками.
     *
     * Пример: Использование устаревших API, некорректное использование API,
     * нежелательные, но не обязательно ошибочные действия.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function warning(object|string $message, array $context = []): void;

    /**
     * Нормальные, но значимые события.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function notice(object|string $message, array $context = []): void;

    /**
     * Интересные события.
     *
     * Пример: Пользователь вошёл в систему, SQL-запросы.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function info(object|string $message, array $context = []): void;

    /**
     * Подробная отладочная информация.
     *
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function debug(object|string $message, array $context = []): void;

    /**
     * Записывает сообщение с произвольным уровнем логирования.
     *
     * @param mixed               $level   Уровень логирования
     * @param object|string       $message Строка или объект с методом __toString
     * @param array<string,mixed> $context Контекст вызова
     */
    public function log(LogLevel $level, string|object $message, array $context = []): void;
}
