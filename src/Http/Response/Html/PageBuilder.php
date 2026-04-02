<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response\Html;

use Vasoft\Joke\Http\Response\Html\Asset\AssetCollection;
use Vasoft\Joke\Http\Response\Html\Asset\AssetFileManager;

/**
 * Конструктор HTML-страницы, управляющий структурой документа.
 *
 * Отвечает за сборку полноценного HTML5-документа из отдельных частей:
 * заголовка (<head>), тела (<body>), подключенных ресурсов (CSS/JS) и мета-информации.
 *
 * Поддерживает:
 * - Управление атрибутами тегов <html> и <body>.
 * - Автоматическое экранирование мета-тегов с учетом заданной кодировки.
 * - Раздельное подключение ресурсов в head и body через AssetCollection.
 * - Добавление произвольных строк в head и конец body (для скриптов аналитики и т.д.).
 */
class PageBuilder
{
    /**
     * Коллекция CSS-стилей для подключения в <head> или <body>.
     */
    public private(set) AssetCollection $css;
    /**
     * Коллекция JavaScript-скриптов для подключения в <head> или <body>.
     */
    public private(set) AssetCollection $js;
    /**
     * Атрибуты тега <html> (например, lang, class).
     * По умолчанию устанавливается lang="ru".
     */
    public private(set) AttributeCollection $htmlAttributes;
    /**
     * Атрибуты тега <body> (например, id, class, data-attributes).
     */
    public private(set) AttributeCollection $bodyAttributes;
    /**
     * Ассоциативный массив мета-тегов вида ['name' => 'content'].
     * Рендерится как <meta name="..." content="...">.
     *
     * @var array<string, string>
     */
    public private(set) array $meta = [];
    /**
     * Заголовок страницы (<title>).
     */
    public private(set) string $title = '';
    /**
     * Кодировка документа. Используется для<meta charset> и экранирования спецсимволов.
     * По умолчанию UTF-8.
     */
    public private(set) string $charset = 'UTF-8';
    /**
     * Коллекция произвольных строк для вставки внутрь тега <head>.
     * Используется для кастомных тегов, которые не обрабатываются явно.
     */
    public private(set) StringCollection $headString;
    /**
     * Коллекция произвольных строк для вставки в конец тега <body>.
     * Часто используется для скриптов аналитики или отладочных панелей.
     */
    public private(set) StringCollection $bottomString;
    /**
     * Основное содержимое страницы (innerHTML тега <body>).
     */
    public private(set) string $content = '';

    /**
     * @var string разделитель между тегами при генерации HTML
     */
    private readonly string $tagSeparator;

    /**
     * Инициализирует конструктор страницы.
     *
     * @param PageBuilderConfig $config      конфигурация билдера
     * @param AssetFileManager  $fileManager менеджер файлов для обработки ассетов (копирование, версионирование)
     */
    public function __construct(
        PageBuilderConfig $config,
        AssetFileManager $fileManager,
    ) {
        $this->tagSeparator = $config->tagSeparator;
        $this->css = new AssetCollection(
            'link',
            'href',
            $fileManager,
            $config->assetsPathCss,
            $this->tagSeparator,
        );
        $this->js = new AssetCollection(
            'script',
            'src',
            $fileManager,
            $config->assetsPathJs,
            $this->tagSeparator,
        );
        $this->htmlAttributes = new AttributeCollection()
            ->set('lang', 'ru');
        $this->bodyAttributes = new AttributeCollection();
        $this->headString = new StringCollection('');
        $this->bottomString = new StringCollection('');
    }

    public function build(): string
    {
        $result = $this->getBeginHtml() . $this->tagSeparator;
        $result .= $this->getHead() . $this->tagSeparator;
        $result .= $this->getBody() . $this->tagSeparator;

        return $result . '</html>';
    }

    private function getHead(): string
    {
        $parts = [];
        if ('' !== $this->title) {
            $parts[] = '<title>' . $this->title . '</title>';
        }
        $parts[] = '<meta charset="' . $this->charset . '">';
        $meta = trim($this->buildMeta());
        if ('' !== $meta) {
            $parts[] = $meta;
        }
        $css = $this->css->buildForHead();
        if ('' !== $css) {
            $parts[] = $css;
        }
        $js = $this->js->buildForHead();
        if ('' !== $js) {
            $parts[] = $js;
        }
        $lines = $this->headString->build();
        if ('' !== $lines) {
            $parts[] = $lines;
        }


        $result = '<head>' . $this->tagSeparator;
        $result .= implode($this->tagSeparator, $parts);

        return $result . $this->tagSeparator . '</head>';
    }

    private function buildMeta(): string
    {
        $meta = [];
        foreach ($this->meta as $name => $value) {
            $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, $this->charset);
            $safeValue = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, $this->charset);
            $meta[] = sprintf('<meta name="%s" content="%s">', $safeName, $safeValue);
        }

        return implode($this->tagSeparator, $meta);
    }

    private function getBody(): string
    {
        $parts = [$this->getBeginBody()];
        if ('' !== $this->content) {
            $parts[] = $this->content;
        }
        $css = $this->css->buildForBody();
        if ('' !== $css) {
            $parts[] = $css;
        }
        $js = $this->js->buildForBody();
        if ('' !== $js) {
            $parts[] = $js;
        }
        $bottomString = $this->bottomString->build();
        if ('' !== $bottomString) {
            $parts[] = $bottomString;
        }

        return implode($this->tagSeparator, $parts) . $this->tagSeparator . '</body>';
    }

    private function getBeginHtml(): string
    {
        $result = '<html';
        $attributes = $this->htmlAttributes->getAttributes();
        if ('' !== $attributes) {
            $result .= ' ' . $attributes;
        }

        return $result . '>';
    }

    private function getBeginBody(): string
    {
        $result = '<body';
        $attributes = $this->bodyAttributes->getAttributes();
        if ('' !== $attributes) {
            $result .= ' ' . $attributes;
        }

        return $result . '>';
    }

    public function addMeta(string $key, string $value): static
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }
}
