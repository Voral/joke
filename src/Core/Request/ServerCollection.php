<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Request;

use Vasoft\Joke\Core\Collections\StringCollection;

/**
 * Коллекция серверных переменных с преобразованием в HTTP-заголовки.
 *
 * Расширяет PropsCollection, добавляя метод для извлечения и нормализации
 * HTTP-заголовков из серверных переменных (в формате $_SERVER).
 * Обрабатывает как стандартные заголовки (HTTP_*), так и специальные
 * переменные контента (CONTENT_TYPE, CONTENT_LENGTH и др.).
 */
class ServerCollection extends StringCollection
{
    /**
     * Извлекает и нормализует HTTP-заголовки из серверных переменных.
     *
     * Преобразует ключи вида HTTP_ACCEPT_LANGUAGE → Accept-Language
     * и добавляет специальные заголовки контента (Content-Type, Content-Length и др.)
     * из соответствующих серверных переменных.
     *
     * @return array<string, string> Ассоциативный массив заголовков в стандартном HTTP-формате
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->props as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
            }
        }
        $headers['Content-Type'] = $this->getStringOrDefault('CONTENT_TYPE', 'text/html');
        $headers['Content-Length'] = $this->getStringOrDefault('CONTENT_LENGTH', '0');
        $headers['Content-Encoding'] = $this->getStringOrDefault('CONTENT_ENCODING', '');
        $headers['Content-Language'] = $this->getStringOrDefault('CONTENT_LANGUAGE', '');
        $headers['Content-MD5'] = $this->getStringOrDefault('CONTENT_MD5', '');

        return $headers;
    }
}
