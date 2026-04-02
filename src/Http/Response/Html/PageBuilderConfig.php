<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response\Html;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;

/**
 * Конфигурация для PageBuilder и системы управления подключаемыми файлами.
 *
 * Определяет базовые пути для сохранения и подключения статических ресурсов (CSS/JS),
 * а также форматирование вывода HTML-тегов.
 */
class PageBuilderConfig extends AbstractConfig
{
    /**
     * Базовый относительный путь для JavaScript-файлов.
     *
     * Используется AssetFileManager для определения директории, куда будут
     * копироваться или ссылаться JS-скрипты, добавленные через PageBuilder.
     *
     * @var string путь относительно корня public-директории (по умолчанию 'assets/js')
     */
    public private(set) string $assetsPathJs = 'assets/js' {
        get {
            return $this->assetsPathJs;
        }
    }
    /**
     * Базовый относительный путь для CSS-файлов.
     *
     * Используется AssetFileManager для определения директории, куда будут
     * копироваться или ссылаться таблицы стилей, добавленные через PageBuilder.
     *
     * @var string путь относительно корня public-директории (по умолчанию 'assets/css')
     */
    public private(set) string $assetsPathCss = 'assets/css' {
        get {
            return $this->assetsPathCss;
        }
    }
    /**
     * Разделитель, используемый при генерации HTML-разметки.
     *
     * Вставляется между тегами для форматирования исходного кода.
     * По умолчанию используется перенос строки ("\n").
     *
     * @var string символ или последовательность символов-разделителей
     */
    public private(set) string $tagSeparator = "\n" {
        get {
            return $this->tagSeparator;
        }
    }

    /**
     * Устанавливает путь для CSS-файлов.
     *
     * Вызов этого метода после "заморозки" конфигурации (вызова guard() извне или передачи в контейнер)
     * приведет к исключению.
     *
     * @param string $assetsPathCss новый относительный путь (например, 'static/styles')
     *
     * @return self для поддержки цепочки вызовов
     *
     * @throws ConfigException При попытке изменить значение после заморозки
     */
    public function setAssetsPathCss(string $assetsPathCss): self
    {
        $this->guard();
        $this->assetsPathCss = $assetsPathCss;

        return $this;
    }

    /**
     * Устанавливает путь для JavaScript-фйлов.
     *
     * Вызов этого метода после "заморозки" конфигурации приведет к исключению.
     *
     * @param string $assetsPathJs новый относительный путь (например, 'static/scripts')
     *
     * @return self для поддержки цепочки вызовов
     *
     * @throws ConfigException При попытке изменить значение после заморозки
     */
    public function setAssetsPathJs(string $assetsPathJs): self
    {
        $this->guard();
        $this->assetsPathJs = $assetsPathJs;

        return $this;
    }

    /**
     * Устанавливает разделитель тегов для генерации HTML.
     *
     * Позволяет изменить форматирование итогового HTML-кода (например, использовать пробелы вместо переносов строк).
     *
     * @param string $tagSeparator строка-разделитель
     *
     * @return self для поддержки цепочки вызовов
     *
     * @throws ConfigException При попытке изменить значение после заморозки
     */
    public function setTagSeparator(string $tagSeparator): self
    {
        $this->guard();
        $this->tagSeparator = $tagSeparator;

        return $this;
    }
}
