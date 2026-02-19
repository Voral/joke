# Маршрутизация

Joke предоставляет гибкую систему маршрутизации, основанную на явной регистрации обработчиков для HTTP-методов и
URI-паттернов. Все маршруты определяются в файлах конфигурации и интегрированы с DI-контейнером и middleware-слоем.

## Конфигурация маршрутов

По умолчанию настройка маршрутов производится в конфигурационных файлах в каталоге `routes`. Путь к файлу маршрутов
указывается при создании приложения в `bootstrap/app.php`:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Vasoft\Joke\Application\Application;
use Vasoft\Joke\Container\ServiceContainer;

return new Application(
    dirname(__DIR__),  // каталог корня проекта
    'routes/web.php',  // Указываем файл Web-маршрутов относительно корня проекта 
    new ServiceContainer()
);

```

> ВАЖНО!  
> Роуты web-маршрутов автоматически оборачиваются в middleware-слои:
> - управление сессией (по умолчанию блокирующий режим)
> - защита от CSRF атак

## Базовое использование

Внутри файла маршрутов доступна переменная `$router` типа `Vasoft\Joke\Routing\Router`. Простейший маршрут
регистрируется так:

```php
use Vasoft\Joke\Routing\Router;

/**
 * @var Router $router
 */
$router->get('/hello', fn() => 'hello world');
```

Обработчик может быть любым вызываемым значением: замыканием, функцией, методом класса или invokable-объектом.

## Поддерживаемые HTTP-методы

Маршрутизатор поддерживает все основные HTTP-методы:

```php
$router->get($path, $handler); // Получение данных  
$router->head($path, $handler); // Получение данных в заголовках
$router->post($path, $handler); // Создание ресурса
$router->put($path, $handler); // Замена существующего ресурса
$router->patсh($path, $handler); // Частично изменение существующего ресурса
$router->delete($path, $handler); // Удаление существующего ресурса
```

Если маршрут должен отвечать на любой метод запроса, то необходимо регистрировать при помощи специального метода:

```php
$router->any($path, $handler);
```

Так же возможно регистрация маршрута на заданный перечень HTTP методов:

```php
use Vasoft\Joke\Http\HttpMethod;

$router->math([HttpMethod::GET,HttpMethod::POST], $handler);
```

## Внедрение зависимостей

Фреймворк автоматически разрешает зависимости через DI-контейнер. Вы можете объявлять в сигнатуре обработчика любые
типизированные параметры, зарегистрированные в контейнере:

```php
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Http\HttpRequest;

/**
 * @var Router $router
 */
$router->get('/hello', function(HttpRequest $request) {
//...
});
```

## Типы обработчиков

Поддерживаются следующие формы обработчиков:

```php
// замыкание
$router->get('/a', fn() => 'hi');
// статический метод через строку
$router->get('/b', 'SomeClass::staticFo');
// Callable в стиле first-class (PHP 8.1+)
$router->get('/c', SomeClass::staticFo(...));
// Массив [класс, метод]
$router->get('/d', [SomeClass::class, 'staticFo']);
// Массив [объект, метод]
$router->get('/f', [$instance, 'staticFo']);
// Invokable-класс
$router->get('/g', InvokeController::class);
```

## Параметры маршрутов

### Основное

Сегменты URI могут быть захвачены как параметры с помощью {имя}:

```php
$router->get('/order/{userId}/{id}', function(int $userId, int $id) {
//...
});
```
Параметры автоматически передаются в обработчик в соответствии с именами и типами.

### Автоматическая десериализация

Если параметр имеет тип, поддерживающий tryFrom() (например, enum), фреймворк попытается преобразовать значение в объект:

```php
$router->get('/order/{status}/', function(OrderStatus $status) {
//...
});
```

### Ограничение параметров

Можно задать шаблон для параметра через `:правило`:

```php
$router->get('/catalog/{section:slug}/{id:int}', function(OrderStatus $status) {
//...
});
```

Доступные правила:

- :int → `\d+`
- :slug → `[a-z0-9\-_]+`
- По умолчанию → `[^/]+`

### Wildcard-параметр (`{*}`)

Для перехвата **любых оставшихся URI** (включая пути со слэшами) используется специальный wildcard-параметр `{*}`:

```php
$router->get('/{*}', fn() => 'Страница не найдена');
```

Этот паттерн:
- совпадает с **любым URI**, включая `/user/123/profile` или `/api/v1/data.json`,
- должен использоваться **в конце списка маршрутов**, чтобы не перекрывать более конкретные правила,
- автоматически передаётся в обработчик под именем **`$path`** как строка:

```php
$router->get('/{*}', function(string $path) {
    return "Запрошен несуществующий путь: {$path}";
});
```

> Wildcard-маршрут особенно полезен для отображения страницы «404 Not Found».

## Именованные маршруты

Каждому маршруту можно присвоить имя для последующего обращения:

```php
$router->get('/profile', fn() => '...','profile');
// Получение маршрута по имени
$route = $router->route('profile');
```

## Группировка маршрутов

Маршруты можно объединять в группы, чтобы применять к ним общие middleware:

```php
$router->get('/hello', fn() => 'hi','hello')
    ->addGroup('filtered')
    ->addGroup('second');
```

Маршруты подключаемые из конфигурационного файла веб-маршрутов автоматически добавляются в группу `web`.

Фреймворк включает enum содержащий стандартные группы `Vasoft\Joke\Routing\StdGroup`.

## Middleware на уровне маршрута

Для каждого маршрута можно назначить дополнительные middleware:

```php
$router->get('/hello', fn() => 'hi','hello')
    ->addMiddleware(CustomMiddlware1::class)
    ->addMiddleware(CustomMiddlware2::class,'myNamesMiddleware');
```
Middleware выполняются до вызова обработчика и могут модифицировать запрос или прерывать выполнение.
> ВНИМАНИЕ!  
> Именованные посредники уровня маршрута могут переопределять именованные посредники уровня маршрутизатора. Т.е.
> выполняться они будут в той позиции которой посредник с этим именем был зарегистрирован на уровне маршрутизатора

## Расширение через контракты

Фреймворк предоставляет интерфейсы для замены компонентов маршрутизации:

- `Vasoft\Joke\Contract\Routing\RouteInterface`
- `Vasoft\Joke\Contract\Routing\RouterInterface`

Чтобы использовать собственную реализацию, зарегистрируйте её в DI контейнере:

```php
// bootstrap/app.php

use Vasoft\Joke\Contract\Routing\RouterInterface;
use Vasoft\Joke\Application\Application;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Container\ServiceContainer;

$container = new ServiceContainer();
$container->registerSingleton(RouterInterface::class, MyRouter::class);
return new Application(dirname(__DIR__), 'routes/web.php', $container);
```

Это позволяет полностью кастомизировать поведение маршрутизатора без изменения ядра.
