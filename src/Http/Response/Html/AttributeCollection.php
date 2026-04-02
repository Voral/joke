<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response\Html;

/**
 * Коллекция HTML-атрибутов с поддержкой безопасного экранирования и гибкого объединения значений.
 *
 * Этот класс управляет набором атрибутов для HTML-тегов. Он обеспечивает:
 * - Автоматическое экранирование значений для защиты от XSS.
 * - Поддержку булевых атрибутов (например, `disabled`, `checked`).
 * - Возможность добавления нескольких значений к одному атрибуту с настраиваемым разделителем
 *   (полезно для атрибутов `class` и `style`).
 */
class AttributeCollection
{
    /**
     * Разделитель по умолчанию для объединения значений атрибутов (пробел).
     * Используется для таких атрибутов, как `class`.
     */
    public const string DEFAULT_SEPARATOR = ' ';
    /**
     * Карта пользовательских разделителей для конкретных атрибутов.
     *
     * @var array<string, string> ключ — имя атрибута, значение — разделитель
     */
    private array $separators = [
        'style' => ';',
    ];
    /**
     * Внутреннее хранилище атрибутов.
     *
     * Структура массива:
     * - Для обычных атрибутов: ['имя_атрибута' => ['значение1', 'значение2', ...]]
     * - Для булевых атрибутов: ['имя_атрибута' => true|false]
     *
     * @var array<string, bool|list<string>>
     */
    private array $attributes = [];

    /**
     * Инициализирует коллекцию атрибутов.
     *
     * @param array<string, bool|string> $attributes Начальный набор атрибутов.
     *                                               Булевы значения будут обработаны как флаги.
     * @param array<string, string>      $separators Пользовательские разделители для конкретных атрибутов.
     *                                               Перезаписывает значения по умолчанию.
     */
    public function __construct(
        array $attributes = [],
        array $separators = [],
    ) {
        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                $this->flag($name, $value);
            } else {
                $this->set($name, $value);
            }
        }
        if ([] !== $separators) {
            $this->separators = $separators;
        }
    }

    /**
     * Устанавливает значение атрибута, заменяя предыдущее.
     *
     * Если атрибут уже существовал, его значение будет перезаписано.
     * Значение сохраняется как единственный элемент в списке.
     *
     * @param string $name  имя атрибута (например, 'id', 'class', 'data-value')
     * @param string $value значение атрибута
     *
     * @return self для поддержки цепочки вызовов
     */
    public function set(string $name, string $value): self
    {
        $this->attributes[$name] = [$value];

        return $this;
    }

    /**
     * Устанавливает или удаляет булевый флаг атрибута.
     *
     * Булевы атрибуты (например, `disabled`, `readonly`, `checked`) не имеют значения в HTML5.
     * Если `$value` равно `true`, атрибут будет присутствовать в выводе.
     * Если `$value` равно `false`, атрибут будет отсутствовать.
     *
     * @param string $name  имя атрибута
     * @param bool   $value состояние флага
     *
     * @return self для поддержки цепочки вызовов
     */
    public function flag(string $name, bool $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Добавляет новое значение к существующему атрибуту.
     *
     * Если атрибут еще не установлен, он создается с указанным значением.
     * Если атрибут уже существует и не является булевым, новое значение добавляется в список.
     * При рендеринге значения будут объединены через разделитель.
     *
     * @param string $name  имя атрибута
     * @param string $value добавляемое значение
     *
     * @return self для поддержки цепочки вызовов
     */
    public function append(string $name, string $value): self
    {
        if (array_key_exists($name, $this->attributes) && !is_bool($this->attributes[$name])) {
            $this->attributes[$name][] = $value;
        } else {
            $this->attributes[$name] = [$value];
        }

        return $this;
    }

    /**
     * Удаляет атрибут из коллекции.
     *
     * @param string $name имя удаляемого атрибута
     *
     * @return self для поддержки цепочки вызовов (fluent interface)
     */
    public function remove(string $name): self
    {
        if (array_key_exists($name, $this->attributes)) {
            unset($this->attributes[$name]);
        }

        return $this;
    }

    /**
     * Генерирует строку HTML-атрибутов для вставки в тег.
     *
     * Все значения автоматически экранируются через {@see htmlspecialchars()} для предотвращения XSS.
     * Булевы атрибуты со значением `false` игнорируются.
     * Множественные значения объединяются через соответствующий разделитель.
     *
     * @return string Строка вида: `id="main" class="btn primary" disabled`.
     *                Возвращает пустую строку, если коллекция пуста.
     */
    public function getAttributes(): string
    {
        $result = [];
        foreach ($this->attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $result[] = $name;
                }
            } else {
                $separator = $this->separators[$name] ?? self::DEFAULT_SEPARATOR;
                $value = implode($separator, $value);
                $result[] = sprintf('%s="%s"', $name, htmlspecialchars($value));
            }
        }

        return implode(' ', $result);
    }

    /**
     * Устанавливает пользовательский разделитель для конкретного атрибута.
     *
     * Позволяет переопределить поведение по умолчанию для таких атрибутов, как `style` (разделитель `;`)
     * или любых кастомных атрибутов, требующих специфичного форматирования.
     *
     * @param string $name      имя атрибута
     * @param string $separator строка-разделитель
     *
     * @return self для поддержки цепочки вызовов
     */
    public function setAttributeSeparator(string $name, string $separator): self
    {
        $this->separators[$name] = $separator;

        return $this;
    }
}
