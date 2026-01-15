<?php

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Core\Collections\PropsCollection;
use Vasoft\Joke\Core\Request\Exceptions\WrongRequestMethodException;

class HttpRequest
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
    }

    public static function fromGlobals(): static
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }
}