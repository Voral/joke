<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response\Html\Asset;

/**
 * Коллекция подключаемых файлов скриптов.
 *
 * @see AssetCollection Базовая логика рендеринга HTML-тегов ассетов
 * @see Asset           Модель отдельного ассета
 */
class ScriptCollection extends AssetCollection
{
    /**
     * Инициализирует коллекцию подключаемых файлов скриптов.
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
        parent::__construct('script', 'src', $assetFileManager, $directoryName, $tagSeparator);
    }

    /**
     * Выполняет окончательное форматирование строки.
     *
     * @param string $attributes строка атрибутов тега
     */
    protected function format(string $attributes): string
    {
        return sprintf('<script %s></script>', $attributes);
    }
}
