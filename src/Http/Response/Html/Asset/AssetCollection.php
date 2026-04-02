<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response\Html\Asset;

use Vasoft\Joke\Exceptions\JokeException;
use Vasoft\Joke\Http\Response\Html\AttributeCollection;

/**
 * Коллекция подключаемых файлов (CSS или JS) с поддержкой дедупликации и управления позицией.
 *
 * Отвечает за хранение списка ресурсов, определение места их подключения (head или body)
 * и генерацию соответствующих HTML-тегов (<link> или <script>).
 *
 * Использует AssetFileManager для обработки путей к файлам (копирование, версионирование).
 */
class AssetCollection
{
    /**
     * Список зарегистрированных подключаемых файлов, индексированный по URL.
     *
     * Ключ массива — исходный URL файлов. Значение — объект Asset.
     * Это обеспечивает автоматическую дедупликацию: повторное добавление того же URL
     * перезаписывает предыдущую запись, сохраняя последние настройки.
     *
     * @var array<string, Asset>
     */
    public array $assets = [];

    /**
     * Инициализирует коллекцию ассетов.
     *
     * @param string           $tagName          имя HTML-тега ('link' для CSS, 'script' для JS)
     * @param string           $valueAttribute   имя атрибута, содержащего путь к файлу ('href' или 'src')
     * @param AssetFileManager $assetFileManager сервис для обработки файловых путей
     * @param string           $directoryName    имя подкаталога, куда будут помещаться файлы при обработке от корня сайта
     * @param string           $tagSeparator     разделитель между тегами при генерации HTML (по умолчанию пустая строка)
     */
    public function __construct(
        protected readonly string $tagName,
        protected readonly string $valueAttribute,
        protected readonly AssetFileManager $assetFileManager,
        private readonly string $directoryName,
        private readonly string $tagSeparator = '',
    ) {}

    /**
     * Добавляет файл для подключения в секции <body>.
     *
     * Логика позиции:
     * - Если файл уже был добавлен в HEAD, он останется там (чтобы не ломать зависимости).
     * - В остальных случаях назначается позиция BODY.
     *
     * @param string                   $url        URL или путь к файлу ресурса
     * @param int                      $order      приоритет сортировки
     * @param null|AttributeCollection $attributes дополнительные атрибуты тега
     */
    public function addToBody(string $url, int $order = 500, ?AttributeCollection $attributes = null): void
    {
        $position
            = array_key_exists($url, $this->assets) && AssetPosition::HEAD === $this->assets[$url]->position
            ? AssetPosition::HEAD
            : AssetPosition::BODY;
        $this->add($url, $attributes, $order, $position);
    }

    /**
     * Добавляет файл для подключения в секции <head>.
     *
     * Если файл с таким URL уже существует, его позиция и параметры обновляются.
     *
     * @param string                   $url        URL или путь к файлу ресурса
     * @param null|AttributeCollection $attributes дополнительные атрибуты тега (например, integrity, crossorigin)
     * @param int                      $order      приоритет сортировки (чем меньше число, тем выше приоритет)
     */
    public function addToHead(string $url, ?AttributeCollection $attributes = null, int $order = 500): void
    {
        $this->add($url, $attributes, $order, AssetPosition::HEAD);
    }

    /**
     * Внутренний метод регистрации или обновления файла.
     *
     * @param string                   $url        URL ресурса
     * @param null|AttributeCollection $attributes атрибуты
     * @param int                      $order      порядок сортировки
     * @param AssetPosition            $position   позиция размещения (HEAD или BODY)
     */
    private function add(string $url, ?AttributeCollection $attributes, int $order, AssetPosition $position): void
    {
        $this->assets[$url] = new Asset($url, $attributes, $order, $position);
    }

    /**
     * Фильтрует файлы, предназначенные для секции <head>.
     *
     * @return array<string, Asset> массив файлов для head
     */
    protected function getForHead(): array
    {
        return array_filter($this->assets, static fn(Asset $item) => AssetPosition::HEAD === $item->position);
    }

    /**
     * Фильтрует файлы, предназначенные для секции <body>.
     *
     * @return array<string, Asset> массив файлов для body
     */
    protected function getForBody(): array
    {
        return array_filter($this->assets, static fn(Asset $item) => AssetPosition::BODY === $item->position);
    }

    /**
     * Генерирует HTML-код для всех файлов, подключенных в <head>.
     *
     * @return string строка с HTML-тегами, разделенными tagSeparator
     */
    public function buildForHead(): string
    {
        $items = $this->getForHead();
        $tags = [];
        foreach ($items as $item) {
            $tags[] = $this->compileRow($item);
        }

        return implode($this->tagSeparator, $tags);
    }

    /**
     * Генерирует HTML-код для всех файлов, подключенных в <body>.
     *
     * Файлы сортируются по полю order перед генерацией.
     *
     * @return string строка с HTML-тегами, разделенными tagSeparator
     */
    public function buildForBody(): string
    {
        $items = $this->getForBody();
        uasort($items, static fn($a, $b) => $a->order <=> $b->order);
        $tags = [];
        foreach ($items as $item) {
            $tags[] = $this->compileRow($item);
        }

        return implode($this->tagSeparator, $tags);
    }

    /**
     * Компилирует одиночный HTML-тег для подключения файла.
     *
     * Обрабатывает URL через AssetFileManager, устанавливает атрибут пути
     * и генерирует самозакрывающийся тег (например, <link ... /> или <script ... />).
     *
     * @param Asset $asset объект файла
     *
     * @return string HTML-тег ресурса
     *
     * @throws JokeException Если файл недоступен
     */
    private function compileRow(Asset $asset): string
    {
        $uri = $this->assetFileManager->process($asset->url, $this->directoryName);
        $asset->attributes->set($this->valueAttribute, $uri);
        $attributes = $asset->attributes->getAttributes();

        return sprintf('<%s %s/>', $this->tagName, $attributes);
    }
}
