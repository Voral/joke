<?php

namespace Vasoft\Joke\Core\Request;

/**
 * Стандартные HTTP методы обрабатываемые фреймворком
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
    case HEAD = 'HEAD';
}
