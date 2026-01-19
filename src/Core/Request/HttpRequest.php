<?php

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Core\Collections\PropsCollection;
use Vasoft\Joke\Core\Collections\Session;
use Vasoft\Joke\Core\Request\Exceptions\WrongRequestMethodException;

class HttpRequest extends Request
{
    public PropsCollection $get {
        get {
            return $this->get;
        }
    }
    public PropsCollection $post {
        get {
            return $this->post;
        }
    }
    public PropsCollection $cookies {
        get {
            return $this->cookies;
        }
    }
    public PropsCollection $files {
        get {
            return $this->files;
        }
    }
    public PropsCollection $server {
        get {
            return $this->server;
        }
    }
    public PropsCollection $props {
        get {
            return $this->props;
        }
    }
    public Session $session {
        get {
            return $this->session;
        }
    }
    public ?PropsCollection $headers = null {
        get {
            if ($this->headers === null) {
                $this->headers = new PropsCollection($this->server->getHeaders());
            }
            return $this->headers;
        }
    }
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

    private ?string $path = null;

    public array $json = [] {
        get => $this->json;
    }

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

    private function isJson(): bool
    {
        $contentType = $this->server->getHeaders()['Content-Type'] ?? '';
        return str_starts_with(strtolower($contentType), 'application/json');
    }

    private function isUrlEncoded(): bool
    {
        $contentType = $this->server->getHeaders()['Content-Type'] ?? '';
        return str_starts_with(strtolower($contentType), 'application/x-www-form-urlencoded');
    }

    public function setProps(array $props): static
    {
        $this->props->reset($props);
        return $this;
    }

    public function getPath(): string
    {
        if ($this->path === null) {
            $path = explode('?', $this->server->get('REQUEST_URI', '/'));
            $this->path = $path[0] ?? '/';
        }
        return $this->path;
    }


    public static function fromGlobals(): static
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
    }
}