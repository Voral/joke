<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response\Html\Asset;

use Vasoft\Joke\Exceptions\JokeException;

/**
 * Коллекция подключаемых файлов CSS.
 *
 * Автоматически добавляет обязательный атрибут rel="stylesheet"
 * к каждому тегу при компиляции, исключая возможность создания
 * невалидного <link>-тега без указания типа связи.
 *
 * @see AssetCollection Базовая логика рендеринга HTML-тегов ассетов
 * @see Asset           Модель отдельного ассета
 */
class CssCollection extends AssetCollection
{
    /**
     * Инициализирует коллекцию подключаемых CSS файлов.
     *
     * @param AssetFileManager $assetFileManager сервис для обработки файловых путей
     * @param string           $directoryName    имя подкаталога, куда будут помещаться файлы при обработке от корня сайта
     * @param string           $tagSeparator     разделитель между тегами при генерации HTML (по умолчанию пустая строка)
     */
    public function __construct(
        AssetFileManager $assetFileManager,
        string $directoryName,
        string $tagSeparator = '',
    ) {
        parent::__construct('link', 'href', $assetFileManager, $directoryName, $tagSeparator);
    }

    /**
     * Компилирует строку <link> для подключаемого файла CSS.
     *
     * Обрабатывает URL через AssetFileManager, устанавливает атрибут пути. Устанавливает атрибут rel="stylesheet".
     *
     * @param Asset $asset объект файла
     *
     * @return string HTML-тег ресурса
     *
     * @throws JokeException Если файл недоступен
     */
    protected function compileRow(Asset $asset): string
    {
        $asset->attributes->set('rel', 'stylesheet');

        return parent::compileRow($asset);
    }

    /**
     * Выполняет окончательное форматирование строки.
     *
     * @param string $attributes строка атрибутов тега
     */
    protected function format(string $attributes): string
    {
        return sprintf('<%s %s/>', $this->tagName, $attributes);
    }
}
