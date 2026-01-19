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

    public function __construct(
        array $get = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        protected ?string $content = null,
    ) {
        $this->get = new PropsCollection($get);
        $this->post = new PropsCollection($post);
        $this->cookies = new PropsCollection($cookies);
        $this->files = new PropsCollection($files);
        $this->server = new ServerCollection($server);
        $this->props = new PropsCollection([]);
        $this->session = new Session([]);
    }

    public function setProps(array $props): static
    {
        $this->props->reset($props);
        return $this;
    }

    public function getPath(): string
    {
        if ($this->path === null) {
            $this->path = $this->server->get('REQUEST_URI', '/');
        }
        return $this->path;
    }


    public static function fromGlobals(): static
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }
}