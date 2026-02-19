# Реализованные middleware

Joke поставляется с набором встроенных middleware, обеспечивающих базовую функциональность веб-приложения: обработку
ошибок, управление сессией и защиту от CSRF-атак.

## ExceptionMiddleware

- Класс: Vasoft\Joke\Middleware\ExceptionMiddleware
- Уровень: глобальный
- Имя: StdMiddleware::EXCEPTION->value

Перехватывает необработанные исключения и преобразует их в корректные HTTP-ответы (например, 500 Internal Server Error)
и исключает ситуацию, когда пользователь увидит «голые» исключения PHP.

Регистрируется автоматически — дополнительная настройка не требуется.

## SessionMiddleware

- Класс: Vasoft\Joke\Middleware\SessionMiddleware
- Уровень: маршрутизатора
- Имя: StdMiddleware::SESSION->value
- Режим: блокирующий (сессия остаётся открытой на всё время обработки запроса)

Запускает сессию, загружает данные и сохраняет их после выполнения цепочки middleware и обработчика. Это поведение по
умолчанию для большинства веб-приложений.

Используется автоматически для всех маршрутов из routes/web.php.

## ReadonlySessionMiddleware

- Класс: Vasoft\Joke\Middleware\ReadonlySessionMiddleware
- Режим: неблокирующий (данные считываются, сессия немедленно закрывается)

Предназначен для сценариев, где:

- сессия нужна только для чтения (например, проверка авторизации),
- важна высокая параллельность (несколько AJAX-запросов от одного пользователя).

> Не сохраняет изменения в сессию. Если вам нужно записать данные — используйте SessionMiddleware.

Чтобы заменить стандартный middleware сессии для всех маршрутов:

```php
use Vasoft\Joke\Middleware\StdMiddleware;
use Vasoft\Joke\Middleware\ReadonlySessionMiddleware;

// В bootstrap/app.php 
$app->addRouteMiddleware(ReadonlySessionMiddleware::class, StdMiddleware::SESSION->value);
```

Чтобы заменить стандартный middleware сессии для конкретного маршрута:

```php
use Vasoft\Joke\Middleware\StdMiddleware;
use Vasoft\Joke\Middleware\ReadonlySessionMiddleware;

// В routes/web.php 
$router->get('/informer', Informer::class)
  ->addMiddleware(ReadonlySessionMiddleware::class, StdMiddleware::SESSION->value);
```

## CsrfMiddleware

- Класс: Vasoft\Joke\Middleware\CsrfMiddleware
- Уровень: маршрутизатора
- Имя: StdMiddleware::CSRF->value
- Применяется к: группе StdGroup::WEB->value (т.е. всем маршрутам из web.php)

Обеспечивает защиту от межсайтовой подделки запроса (CSRF):

1. Генерирует токен, если его нет в сессии (csrf_token).
2. Для небезопасных методов (POST, PUT, DELETE и др.) проверяет наличие токена:
    - в параметрах запроса: ?csrf_token=... или csrf_token=... (POST),
    - в заголовке: X-Csrf-Token: ....
3. При несоответствии выбрасывает CsrfMismatchException (HTTP 403).

Токен генерируется автоматически. Вам нужно только передать его в форму или заголовок.

