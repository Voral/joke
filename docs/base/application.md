# Приложение

Класс `Vasoft\Joke\Application\Application` является **точкой входа и центральным оркестратором** всего фреймворка. Он
управляет загрузкой маршрутов, выполнением middleware и обработкой HTTP-запросов от начала до конца.

## Жизненный цикл запроса

1. **Создание приложения**  
   В `bootstrap/app.php` создаётся экземпляр `Application` с DI-контейнером и путём к файлу маршрутов:

   ```php
   return new Application(
       dirname(__DIR__),    // базовый путь проекта
       'routes/web.php',    // файл маршрутов
       new ServiceContainer()
   );
   ```

2. **Точка входа**  
   В `public/index.php` создаётся HTTP-запрос из глобальных переменных и передаётся в приложение:

   ```php
   $app = require_once __DIR__ . '/../bootstrap/app.php';
   $app->handle(HttpRequest::fromGlobals());
   ```

3. **Обработка запроса**  
   Приложение выполняет следующие шаги:
    - Загружает маршруты из `routes/web.php`,
    - Выполняет **глобальные middleware** (до определения маршрута),
    - Находит подходящий маршрут,
    - Выполняет **middleware маршрутизатора** и **middleware маршрута**,
    - Запускает обработчик маршрута,
    - Отправляет ответ клиенту.

## Автоматически регистрируемые компоненты

При создании `Application` автоматически настраиваются:

### Глобальные middleware

- `ExceptionMiddleware` (имя: `exception`) — перехватывает все исключения и преобразует их в HTTP-ответы.

### Middleware маршрутизатора

- `SessionMiddleware` (имя: `session`) — управляет сессией в **блокирующем режиме**,
- `CsrfMiddleware` (имя: `csrf`) — применяется **только к маршрутам из группы `web`**.

> Все маршруты, определённые в `routes/web.php`, автоматически получают группу `web`.

## Обработка ответа

Приложение гибко обрабатывает результат обработчика маршрута:

| Тип результата                       | Действие                       |
|--------------------------------------|--------------------------------|
| Строка, число, `null`                | Оборачивается в `HtmlResponse` |
| Массив                               | Оборачивается в `JsonResponse` |
| Экземпляр `Response` (или наследник) | Используется напрямую          |

Примеры:

```php
// Вернёт HTML
$router->get('/text', fn() => '<h1>Hello</h1>');

// Вернёт JSON
$router->get('/api', fn() => ['status' => 'ok']);

// Явный ответ
$router->get('/custom', fn() => (new HtmlResponse())->setBody('OK')->setStatus(ResponseStatus::NOT_FOUND));
```

Это позволяет писать краткий код для простых случаев и сохранять полный контроль — для сложных.

## Регистрация middleware

Вы можете расширять поведение приложения через два метода:

### Глобальные middleware (до определения маршрута)

```php
$app->addMiddleware(CorsMiddleware::class);
```

Полезны для логирования, CORS, обработки ошибок.

### Middleware маршрутизатора (после определения маршрута)

```php
$app->addRouteMiddleware(AuthMiddleware::class, 'auth', ['api']);
```

Можно привязать к конкретным группам маршрутов.

> Именованные middleware можно **переопределять** — новый заменит старый, сохранив позицию в цепочке.

## Интеграция с DI-контейнером

Приложение автоматически регистрирует текущий `HttpRequest` в контейнере:

```php
$this->serviceContainer->registerSingleton(HttpRequest::class, $request);
```

Это позволяет внедрять запрос в любые сервисы или обработчики:

```php
function handle(UserService $users, HttpRequest $request) {
    $id = $request->uri()->pathSegment(2);
    // ...
}
```

## Обработка ошибок

Все необработанные исключения (включая `NotFoundException` при отсутствии маршрута) перехватываются
`ExceptionMiddleware` и преобразуются в корректные HTTP-ответы (обычно 500 или 404).

> Разработчику **не нужно** оборачивать `$app->handle()` в `try/catch` — всё обрабатывается автоматически.

## Пример полной настройки

```php
// bootstrap/app.php
session_set_cookie_params([
    'samesite' => 'Lax',
    'secure' => $_SERVER['HTTPS'] ?? false,
    'httponly' => true,
]);

$container = new ServiceContainer();
$container->registerSingleton(Logger::class, FileLogger::class);

return new Application(
    dirname(__DIR__),
    'routes/web.php',
    $container
)->addMiddleware(CorsMiddleware::class)
 ->addRouteMiddleware(AuthMiddleware::class, 'auth', ['admin']);
```

