<?php

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Core\Collections\PropsCollection;
use Vasoft\Joke\Core\Collections\Session;
use Vasoft\Joke\Core\Request\Exceptions\WrongRequestMethodException;

/**
 * HTTP-запрос, инкапсулирующий данные входящего запроса.
 *
 * Предоставляет объектную обёртку над суперглобальными переменными ($_GET, $_POST и т.д.)
 * и разбирает тело запроса в зависимости от Content-Type (JSON, URL-encoded).
 * Используется как основной объект запроса во всём фреймворке.
 */
class HttpRequest extends Request
{
    /**
     * Данные GET-параметров запроса.
     *
     * @var PropsCollection
     */
    public PropsCollection $get {
        get {
            return $this->get;
        }
    }
    /**
     * Данные POST-параметров запроса.
     *
     * @var PropsCollection
     */
    public PropsCollection $post {
        get {
            return $this->post;
        }
    }
    /**
     * Данные cookie запроса.
     *
     * @var PropsCollection
     */
    public PropsCollection $cookies {
        get {
            return $this->cookies;
        }
    }
    /**
     * Информация о загруженных файлах.
     *
     * @var PropsCollection
     */
    public PropsCollection $files {
        get {
            return $this->files;
        }
    }
    /**
     * Серверные переменные (аналог $_SERVER).
     *
     * @var ServerCollection
     */
    public PropsCollection $server {
        get {
            return $this->server;
        }
    }
    /**
     * Произвольные свойства, привязанные к запросу (например, параметры маршрута).
     *
     * @var PropsCollection
     */
    public PropsCollection $props {
        get {
            return $this->props;
        }
    }
    /**
     * Хранилище данных сессии.
     *
     * @var Session
     */
    public Session $session {
        get {
            return $this->session;
        }
    }
    /**
     * Заголовки HTTP-запроса.
     *
     * Лениво инициализируется из серверных переменных при первом обращении.
     *
     * @var PropsCollection|null
     */
    public ?PropsCollection $headers = null {
        get {
            if ($this->headers === null) {
                $this->headers = new PropsCollection($this->server->getHeaders());
            }
            return $this->headers;
        }
    }
    /**
     * HTTP-метод запроса.
     *
     * Лениво определяется из SERVER['REQUEST_METHOD'] и преобразуется в HttpMethod enum.
     * Выбрасывает исключение при неизвестном методе.
     *
     * @var HttpMethod|null
     */
    public ?HttpMethod $method = null {
        get {
            if ($this->method === null) {
                $method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
                $this->method = HttpMethod::tryFrom($method);
                if ($this->method === null) {
                    throw new WrongRequestMethodException($method);
                }
            }
            return $this->method;
        }
    }
    /**
     * Кэшированный путь URI без query string.
     *
     * @var string|null
     */
    private ?string $path = null;

    /**
     * Разобранные JSON-данные из тела запроса.
     *
     * Заполняется автоматически, если Content-Type = application/json.
     *
     * @var array
     */
    public array $json = [] {
        get => $this->json;
    }

    /**
     * Конструктор запроса.
     *
     * Автоматически парсит тело запроса в зависимости от Content-Type:
     * - application/json → массив в свойство $json
     * - application/x-www-form-urlencoded → параметры в $post
     *
     * @param array<string, mixed> $get GET-параметры
     * @param array<string, mixed> $post POST-параметры
     * @param array<string, mixed> $cookies Cookie
     * @param array<string, mixed> $files Информация о загруженных файлах
     * @param array<string, string> $server Серверные переменные
     * @param string|null $rawBody Сырое тело запроса (например, из php://input)
     */
    public function __construct(
        array $get = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        protected ?string $rawBody = null,
    ) {
        $this->get = new PropsCollection($get);
        $this->post = new PropsCollection($post);
        $this->cookies = new PropsCollection($cookies);
        $this->files = new PropsCollection($files);
        $this->server = new ServerCollection($server);
        $this->props = new PropsCollection([]);
        $this->session = new Session([]);
        if ($this->isUrlEncoded()) {
            $params = [];
            parse_str($rawBody, $params);
            $this->post->reset($params);
        } elseif ($this->isJson()) {
            $this->json = json_decode($rawBody, true) ?: [];
        }
    }

    /**
     * Проверяет, является ли Content-Type application/json.
     *
     * @return bool
     */
    private function isJson(): bool
    {
        $contentType = $this->server->getHeaders()['Content-Type'] ?? '';
        return str_starts_with(strtolower($contentType), 'application/json');
    }

    /**
     * Проверяет, является ли Content-Type application/x-www-form-urlencoded.
     *
     * @return bool
     */
    private function isUrlEncoded(): bool
    {
        $contentType = $this->server->getHeaders()['Content-Type'] ?? '';
        return str_starts_with(strtolower($contentType), 'application/x-www-form-urlencoded');
    }

    /**
     * Устанавливает произвольные свойства запроса.
     *
     * Обычно используется для передачи параметров маршрута или контекстных данных.
     *
     * @param array<string, mixed> $props Ассоциативный массив свойств
     * @return static
     */
    public function setProps(array $props): static
    {
        $this->props->reset($props);
        return $this;
    }

    /**
     * Возвращает путь URI без query string.
     *
     * Например, для /user/123?tab=profile вернёт /user/123.
     *
     * @return string
     */
    public function getPath(): string
    {
        if ($this->path === null) {
            $path = explode('?', $this->server->get('REQUEST_URI', '/'));
            $this->path = $path[0] ?? '/';
        }
        return $this->path;
    }

    /**
     * Создаёт экземпляр запроса из суперглобальных переменных PHP.
     *
     * Используется в точке входа (public/index.php) для создания запроса
     * на основе реальных данных текущего HTTP-запроса.
     *
     * @return static
     */
    public static function fromGlobals(): static
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
    }
}