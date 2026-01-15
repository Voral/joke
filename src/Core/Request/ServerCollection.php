<?php

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Core\Collections\PropsCollection;

class ServerCollection extends PropsCollection
{
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->props as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
            }
        }
        $headers['Content-Type'] = $this->get('CONTENT_TYPE', 'text/html');
        $headers['Content-Length'] = $this->get('CONTENT_LENGTH', 0);
        $headers['Content-Encoding'] = $this->get('CONTENT_ENCODING', '');
        $headers['Content-Language'] = $this->get('CONTENT_LANGUAGE', '');
        $headers['Content-MD5'] = $this->get('CONTENT_MD5', '');
        return $headers;
    }
}