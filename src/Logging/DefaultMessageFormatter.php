<?php

declare(strict_types=1);

namespace Vasoft\Joke\Logging;

use Vasoft\Joke\Contract\Logging\MessageFormatterInterface;

/**
 * Стандартная реализация форматирования лог-сообщений.
 *
 * Выполняет интерполяцию плейсхолдеров вида `{key}` на основе контекста, безопасно обрабатывает объекты,
 * массивы и цепочки исключений.
 *
 * Используется по умолчанию в {@see Logger}, если не задан кастомный форматтер.
 */
class DefaultMessageFormatter implements MessageFormatterInterface
{
    /**
     * Создаёт форматтер с указанным ограничением глубины сериализации массивов.
     *
     * @param int $maxArrayDepth Максимальная глубина рекурсии при преобразовании вложенных массивов (по умолчанию — 3)
     */
    public function __construct(private readonly int $maxArrayDepth) {}

    /**
     * Заменяет плейсхолдеры вида `{key}` в сообщении на значения из контекста.
     *
     * Правила преобразования:
     * - Сообщение как строку или объект с методом `__toString()` (в том числе и Throwable).
     * - Объекты в контексте: преобразуются через `__toString()` (в том числе и Throwable).
     * - Массивы в контексте: сериализуются.
     * - Прочие объекты → пустая строка.
     *
     * @param object|string $message Исходное сообщение
     * @param array         $context Контекст для подстановки
     *
     * @return string Интерполированное сообщение
     */
    public function interpolate(object|string $message, array $context = []): string
    {
        if (is_object($message)) {
            $message = $this->interpolateObject($message);
        }
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_object($value)) {
                $value = $this->interpolateObject($value);
            } elseif (is_array($value)) {
                $value = $this->arrayToString($value, $this->maxArrayDepth);
            }
            $replace['{' . $key . '}'] = $value;
        }

        return strtr($message, $replace);
    }

    /**
     * Рекурсивно преобразует массив в строку с ограничением глубины вложенности.
     *
     * Используется для безопасной сериализации контекста при логировании. При превышении глубины вложенности
     * вставляется маркер `[...]`.
     *
     * @param array $arr   Массив для преобразования
     * @param int   $depth Текущая глубина рекурсии (уменьшается на каждом уровне)
     *
     * @return string Строковое представление массива
     */
    private function arrayToString(array $arr, int $depth = 3): string
    {
        if ($depth <= 0) {
            return '[...]';
        }
        $result = [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $result[] = "{$k} => " . $this->arrayToString($v, $depth - 1);
            } elseif (is_object($v)) {
                $result[] = "{$k} => " . $this->interpolateObject($v);
            } else {
                $result[] = "{$k} => " . print_r($v, true);
            }
        }

        return '[' . implode(', ', $result) . ']';
    }

    /**
     * Преобразует объект в строку для подстановки в сообщение.
     *
     * @param object $object Объект для преобразования
     *
     * @return string Результат преобразования
     */
    private function interpolateObject(object $object): string
    {
        if (method_exists($object, '__toString')) {
            return (string) $object;
        }

        return '';
    }
}
