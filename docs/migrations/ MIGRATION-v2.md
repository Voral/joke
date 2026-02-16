# Миграция на v2.0

В версии 2.0 удалено пространство имён `Vasoft\Joke\Core`.  
Все компоненты перемещены в логические корневые namespace'ы.

## Таблица миграции классов (v1.x → v2.0)

| Старый класс                                             | Новый класс                                        |
|----------------------------------------------------------|----------------------------------------------------|
| `Vasoft\Joke\Core\Application`                           | `Vasoft\Joke\Application\Application`              |
| `Vasoft\Joke\Core\ServiceContainer`                      | `Vasoft\Joke\Container\ServiceContainer`           |
| `Vasoft\Joke\Core\BaseContainer`                         | `Vasoft\Joke\Container\BaseContainer`              |
| `Vasoft\Joke\Core\ParameterResolver`                     | `Vasoft\Joke\Container\ParameterResolver`          |
| `Vasoft\Joke\Core\Routing\Route`                         | `Vasoft\Joke\Routing\Route`                        |
| `Vasoft\Joke\Core\Routing\Router`                        | `Vasoft\Joke\Routing\Router`                       |
| `Vasoft\Joke\Core\Routing\StdGroup`                      | `Vasoft\Joke\Routing\StdGroup`                     |
| `Vasoft\Joke\Core\HttpRequest`                           | `Vasoft\Joke\Http\HttpRequest`                     |
| `Vasoft\Joke\Core\Request\HttpMethod`                    | `Vasoft\Joke\Http\HttpMethod`                      |
| `Vasoft\Joke\Core\Request\ServerCollection`              | `Vasoft\Joke\Http\ServerCollection`                |
| `Vasoft\Joke\Core\Response\Response`                     | `Vasoft\Joke\Http\Response\Response`               |
| `Vasoft\Joke\Core\Response\JsonResponse`                 | `Vasoft\Joke\Http\Response\JsonResponse`           |
| `Vasoft\Joke\Core\Response\HtmlResponse`                 | `Vasoft\Joke\Http\Response\HtmlResponse`           |
| `Vasoft\Joke\Core\Response\BinaryResponse`               | `Vasoft\Joke\Http\Response\BinaryResponse`         |
| `Vasoft\Joke\Core\Response\ResponseStatus`               | `Vasoft\Joke\Http\Response\ResponseStatus`         |
| `Vasoft\Joke\Core\Middlewares\CsrfMiddleware`            | `Vasoft\Joke\Middleware\CsrfMiddleware`            |
| `Vasoft\Joke\Core\Middlewares\ExceptionMiddleware`       | `Vasoft\Joke\Middleware\ExceptionMiddleware`       |
| `Vasoft\Joke\Core\Middlewares\SessionMiddleware`         | `Vasoft\Joke\Middleware\SessionMiddleware`         |
| `Vasoft\Joke\Core\Middlewares\ReadonlySessionMiddleware` | `Vasoft\Joke\Middleware\ReadonlySessionMiddleware` |
| `Vasoft\Joke\Core\Middlewares\StdMiddleware`             | `Vasoft\Joke\Middleware\StdMiddleware`             |
| `Vasoft\Joke\Core\Middlewares\MiddlewareCollection`      | `Vasoft\Joke\Middleware\MiddlewareCollection`      |
| `Vasoft\Joke\Core\Middlewares\MiddlewareDto`             | `Vasoft\Joke\Middleware\MiddlewareDto`             |
| `Vasoft\Joke\Core\Collections\HeadersCollection`         | `Vasoft\Joke\Collections\HeadersCollection`        |
| `Vasoft\Joke\Core\Collections\PropsCollection`           | `Vasoft\Joke\Collections\PropsCollection`          |
| `Vasoft\Joke\Core\Collections\ReadonlyPropsCollection`   | `Vasoft\Joke\Collections\ReadonlyPropsCollection`  |
| `Vasoft\Joke\Core\Collections\StringCollection`          | `Vasoft\Joke\Collections\StringCollection`         |
| `Vasoft\Joke\Core\Collections\Session`                   | `Vasoft\Joke\Session\SessionCollection`            |
| `Vasoft\Joke\Core\Request\Request`                       | `Vasoft\Joke\Foundation\Request`                   |
| `Vasoft\Joke\Types\TypeConverter`                        | `Vasoft\Joke\Support\Types\TypeConverter`          |
| `Vasoft\Joke\Core\Request\Request`                       | `Vasoft\Joke\Foundation\Request`                   |
| `Vasoft\Joke\Core\Collections\Session`                   | `Vasoft\Joke\Session\SessionCollection`            |
| `Vasoft\Joke\Types\TypeConverter`                        | `Vasoft\Joke\Support\Types\TypeConverter`          |

## Обратная совместимость

- **Версия 1.2+**:  
  Все старые классы работают, но помечены как `@deprecated`.  
  При использовании выводится предупреждение с указанием нового пути.

- **Версия 2.0**:  
  Старые классы будут **полностью удалены**.  
  Код необходимо обновить до новых namespace'ов.

> Каждый устаревший файл содержит явное указание на новый класс — достаточно открыть его или посмотреть подсказку в IDE.