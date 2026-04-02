<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response\Html\Asset;

use Vasoft\Joke\Http\Response\Html\AttributeCollection;

/**
 * Представление одного подключаемого к HTML странице файла (CSS или JS файла).
 *
 * Является неизменяемым (readonly) объектом данных, хранящим информацию
 * о пути к файлу, его атрибутах, порядке загрузки и позиции в документе.
 */
readonly class Asset
{
    /**
     * Коллекция HTML-атрибутов для тега ассета.
     *
     * Например: integrity, crossorigin, async, defer и т.д.
     * Инициализируется пустой коллекцией, если не передана явно.
     */
    public AttributeCollection $attributes;

    /**
     * Инициализирует объект файла.
     *
     * @param string                   $url        URL или путь к файлу ресурса
     * @param null|AttributeCollection $attributes Дополнительные атрибуты тега. Если null, создается пустая коллекция.
     * @param int                      $order      приоритет сортировки при выводе (чем меньше число, тем выше приоритет)
     * @param AssetPosition            $position   позиция размещения в HTML-документе (HEAD или BODY)
     */
    public function __construct(
        public string $url,
        ?AttributeCollection $attributes,
        public int $order,
        public AssetPosition $position,
    ) {
        $this->attributes = $attributes ?? new AttributeCollection();
    }
}
